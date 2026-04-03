<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCard;
use App\Services\AuditLogService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private QrCodeService $qrService) {}

    // ─── Staff Login ─────────────────────────────────────────────────────────────

    public function showStaffLogin(): Response
    {
        return Inertia::render('Auth/StaffLogin');
    }

    public function staffLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username' => $request->email,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            AuditLogService::loginFailed($request->email);
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        // Only staff roles can use this login
        if (!$user->isStaff()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Please use the beneficiary portal to log in.',
            ]);
        }

        if (!$user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Contact your administrator.',
            ]);
        }

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        AuditLogService::loginSuccess($user->role);
        $request->session()->regenerate();

        return $this->redirectByRole($user->role);
    }

    // ─── Beneficiary Login ────────────────────────────────────────────────────────

    public function showBeneficiaryLogin(): Response
    {
        return Inertia::render('Auth/BeneficiaryLogin');
    }

    /**
     * Beneficiary logs in via:
     * (a) Unique ID + Password, or
     * (b) QR Code Payload + Password
     */
    public function beneficiaryLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required', 'string'],  // unique_id or qr payload
            'password'   => ['required', 'string'],
        ]);

        $identifier = trim($request->identifier);

        // Resolve unique_id from QR payload if needed
        if (!str_starts_with(strtoupper($identifier), '4PS-')) {
            $uid = $this->qrService->decodeForLogin($identifier);
            if (!$uid) {
                throw ValidationException::withMessages(['identifier' => 'Invalid QR code or Unique ID.']);
            }
            $identifier = $uid;
        }

        // Find beneficiary
        $beneficiary = Beneficiary::where('unique_id', strtoupper($identifier))
            ->with(['user', 'card'])
            ->first();

        if (!$beneficiary || !$beneficiary->user) {
            AuditLogService::loginFailed($identifier);
            throw ValidationException::withMessages(['identifier' => 'Beneficiary not found in the system.']);
        }

        if ($beneficiary->status !== 'active') {
            throw ValidationException::withMessages(['identifier' => "Account is {$beneficiary->status}. Contact your DSWD office."]);
        }

        // Validate against the active card's hashed password OR user account
        $user = $beneficiary->user;
        if (!Hash::check($request->password, $user->password)) {
            AuditLogService::loginFailed($identifier);
            throw ValidationException::withMessages(['password' => 'Incorrect password.']);
        }

        Auth::login($user, $request->boolean('remember'));

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Track first login on the card
        $activeCard = $beneficiary->cards()->where('is_active', true)->first();
        if ($activeCard && $activeCard->is_first_login) {
            $activeCard->update(['first_login_at' => now()]);
        }

        AuditLogService::loginSuccess('beneficiary');
        $request->session()->regenerate();

        // Force password change on first login
        if ($user->must_change_password) {
            return redirect()->route('beneficiary.password.change');
        }

        return redirect()->route('beneficiary.dashboard');
    }

    // ─── Password Change (First Login) ───────────────────────────────────────────

    public function showChangePassword(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => [
                'required', 'string', 'confirmed', 'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ]);

        $user = Auth::user();
        $user->update([
            'password'            => Hash::make($request->password),
            'must_change_password'=> false,
        ]);

        // Update card password changed timestamp
        if ($user->beneficiary) {
            $user->beneficiary->cards()->where('is_active', true)->update([
                'is_first_login'     => false,
                'password_changed_at'=> now(),
                'default_password_plain' => null, // Clear plaintext
            ]);
        }

        AuditLogService::log('password_changed', $user, [], [], 'Password changed by user');

        return redirect()->route('beneficiary.dashboard')
            ->with('success', 'Password changed successfully! Welcome to SECURE 4Ps.');
    }

    // ─── Logout ──────────────────────────────────────────────────────────────────

    public function logout(Request $request): RedirectResponse
    {
        $role = Auth::user()?->role;
        AuditLogService::log('logout', null, [], [], 'User logged out', 'auth');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Route to appropriate login page
        return $role === 'beneficiary'
            ? redirect()->route('beneficiary.login')
            : redirect()->route('staff.login');
    }

    // ─── Role-based Redirect ──────────────────────────────────────────────────────

    private function redirectByRole(string $role): RedirectResponse
    {
        return redirect()->intended(match ($role) {
            'superadmin'          => route('superadmin.dashboard'),
            'admin'               => route('admin.dashboard'),
            'compliance_verifier' => route('verifier.dashboard'),
            'field_officer'       => route('officer.dashboard'),
            default               => route('staff.login'),
        });
    }
}
