<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('office')->staff()->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ilike', "%{$s}%")
                  ->orWhere('email', 'ilike', "%{$s}%")
                  ->orWhere('employee_id', 'ilike', "%{$s}%");
            });
        }

        if ($request->filled('role'))   $query->where('role', $request->role);
        if ($request->filled('status')) $query->where('is_active', $request->status === 'active');

        $users   = $query->paginate(20)->withQueryString();
        $offices = Office::active()->get(['id', 'name']);

        return Inertia::render('Admin/Users/Index', compact('users', 'offices'));
    }

    public function create(): Response
    {
        $offices = Office::active()->get(['id', 'name']);
        return Inertia::render('Admin/Users/Create', compact('offices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'email'          => 'required|email|unique:users',
            'username'       => 'required|string|max:50|unique:users|alpha_dash',
            'role'           => 'required|in:admin,compliance_verifier,field_officer',
            'office_id'      => 'nullable|exists:offices,id',
            'employee_id'    => 'nullable|string|unique:users',
            'contact_number' => 'nullable|string|max:20',
            'position'       => 'nullable|string|max:100',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            ...$validated,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        AuditLogService::created($user);

        return redirect()->route('admin.users.index')
            ->with('success', "Staff account created for {$user->name}.");
    }

    public function show(User $user): Response
    {
        $user->load('office');
        return Inertia::render('Admin/Users/Show', compact('user'));
    }

    public function edit(User $user): Response
    {
        $offices = Office::active()->get(['id', 'name']);
        return Inertia::render('Admin/Users/Edit', compact('user', 'offices'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'email'          => "required|email|unique:users,email,{$user->id}",
            'role'           => 'required|in:admin,compliance_verifier,field_officer',
            'office_id'      => 'nullable|exists:offices,id',
            'contact_number' => 'nullable|string|max:20',
            'position'       => 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ]);

        $old = $user->toArray();

        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            unset($validated['is_active']);
        }

        $user->update($validated);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        AuditLogService::updated($user, $old, $user->fresh()->toArray());

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'Staff account updated successfully.');
    }


    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        AuditLogService::deleted($user);
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User account deleted.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        AuditLogService::log("user_{$status}", $user, [], [], "User account {$status}");
        return back()->with('success', "Account {$status} for {$user->name}.");
    }
}
