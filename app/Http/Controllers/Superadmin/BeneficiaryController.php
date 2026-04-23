<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\FamilyMember;
use App\Models\Office;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BeneficiaryCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiaryController extends Controller
{
    public function __construct(private BeneficiaryCardService $cardService) {}

    public function index(Request $request): Response
    {
        $query = Beneficiary::with(['office', 'card', 'creator'])
            ->withCount(['familyMembers', 'proxies'])
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('unique_id', 'ilike', "%{$s}%")
                  ->orWhere('first_name', 'ilike', "%{$s}%")
                  ->orWhere('last_name', 'ilike', "%{$s}%")
                  ->orWhere('listahanan_id', 'ilike', "%{$s}%");
            });
        }

        if ($request->filled('barangay')) {
            $query->where('barangay', $request->barangay);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by card status: 'none' = no active card, 'issued' = has active card
        if ($request->filled('card')) {
            if ($request->card === 'none') {
                $query->whereDoesntHave('cards', fn($q) => $q->where('is_active', true));
            } elseif ($request->card === 'issued') {
                $query->whereHas('cards', fn($q) => $q->where('is_active', true));
            }
        }

        $beneficiaries = $query->paginate(20)->withQueryString();
        $offices       = Office::active()->orderBy('name')->get(['id', 'name', 'barangay']);
        $barangays     = Beneficiary::distinct()->orderBy('barangay')->pluck('barangay');

        return Inertia::render('Superadmin/Beneficiaries/Index', compact(
            'beneficiaries', 'offices', 'barangays'
        ));
    }

    public function create(): Response
    {
        $offices = Office::active()->orderBy('name')->get(['id', 'name', 'barangay', 'type']);
        return Inertia::render('Superadmin/Beneficiaries/Create', compact('offices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'listahanan_id'      => 'nullable|string|unique:beneficiaries',
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'middle_name'        => 'nullable|string|max:100',
            'suffix'             => 'nullable|string|max:10',
            'birthdate'          => 'required|date|before:today',
            'sex'                => 'required|in:male,female',
            'civil_status'       => 'required|in:single,married,widowed,separated,live-in',
            'contact_number'     => 'nullable|string|max:20',
            'house_no'           => 'nullable|string|max:50',
            'street'             => 'nullable|string|max:100',
            'purok'              => 'nullable|string|max:50',
            'barangay'           => 'required|string|max:100',
            'office_id'          => 'nullable|exists:offices,id',
            'enrollment_date'    => 'nullable|date',
            'remarks'            => 'nullable|string',
            'photo'              => 'nullable|image|max:3072',

            // Family members
            'family_members'     => 'nullable|array|max:20',
            'family_members.*.first_name'      => 'required|string',
            'family_members.*.last_name'       => 'required|string',
            'family_members.*.birthdate'       => 'required|date',
            'family_members.*.sex'             => 'required|in:male,female',
            'family_members.*.relationship'    => 'required|string',
            'family_members.*.education_level' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $uniqueId = Beneficiary::generateUniqueId();

            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store("photos/{$uniqueId}", 'public');
            }

            // Create beneficiary — defaults to 'inactive' until admin approves
            // their physical documentary requirements and activates their account.
            $beneficiary = Beneficiary::create([
                ...$validated,
                'unique_id'           => $uniqueId,
                'household_head_name' => $validated['first_name'].' '.$validated['last_name'],
                'city'                => 'Lipa City',
                'province'            => 'Batangas',
                'zip_code'            => '4217',
                'photo_path'          => $photoPath,
                'status'              => 'inactive',
                'created_by'          => auth()->id(),
            ]);

            // Create family members
            foreach ($validated['family_members'] ?? [] as $member) {
                $age = Carbon::parse($member['birthdate'])->diffInYears(now());
                $isSchoolAge = $age >= 3 && $age <= 18;
                $beneficiary->allFamilyMembers()->create([
                    ...$member,
                    'is_school_age'   => $isSchoolAge,
                    'is_under_five'   => $age <= 5,
                    'is_active'       => true,
                    // Ensure education_level always has a valid enum value
                    'education_level' => !empty($member['education_level'])
                        ? $member['education_level']
                        : 'not_applicable',
                ]);
            }

            // Create portal user account — inactive until beneficiary is approved
            $user = User::create([
                'name'                => $beneficiary->full_name,
                'username'            => strtolower(str_replace('-', '', $uniqueId)),
                'email'               => null,
                'password'            => Hash::make('temp'),
                'role'                => 'beneficiary',
                'office_id'           => $beneficiary->office_id,
                'is_active'           => false,
                'must_change_password'=> true,
            ]);

            $beneficiary->update(['user_id' => $user->id]);

            // NOTE: Card is NOT issued here.
            // The admin must first verify the physical documents (Birth Certificates,
            // Valid ID, School/Health Documents, Barangay Certificate, Photo 1x1)
            // then activate the beneficiary. The card is issued upon activation.

            AuditLogService::created($beneficiary, $beneficiary->toArray());
        });

        return redirect()->route('superadmin.beneficiaries.index')
            ->with('success', 'Beneficiary registered and set to INACTIVE. Activate their account after verifying submitted documents.');
    }

    public function show(int $id): Response
    {
        $beneficiary = Beneficiary::with([
            'office', 'user', 'card', 'cards',
            'familyMembers', 'proxies', 'documents',
            'complianceRecords.verifier',
            'grantCalculations.distributionEvent',
            'distributions.distributionEvent',
        ])->findOrFail($id);

        return Inertia::render('Superadmin/Beneficiaries/Show', compact('beneficiary'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);

        $validated = $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'contact_number'  => 'nullable|string|max:20',
            'barangay'        => 'required|string|max:100',
            'office_id'       => 'nullable|exists:offices,id',
            'status'          => 'required|in:active,inactive,suspended,graduated,delisted',
            'remarks'         => 'nullable|string',
        ]);

        $old = $beneficiary->toArray();
        $beneficiary->update($validated);

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store("photos/{$beneficiary->unique_id}", 'public');
            $beneficiary->update(['photo_path' => $photoPath]);
        }

        AuditLogService::updated($beneficiary, $old, $beneficiary->fresh()->toArray());

        return back()->with('success', 'Beneficiary record updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);
        AuditLogService::deleted($beneficiary);
        $beneficiary->delete();

        return redirect()->route('superadmin.beneficiaries.index')
            ->with('success', 'Beneficiary record deleted.');
    }

    /**
     * Activate a beneficiary after verifying their physical documentary requirements.
     * This sets status → active, enables their portal account, and issues their QR card.
     */
    public function activate(int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::with(['user', 'cards'])->findOrFail($id);

        if ($beneficiary->status === 'active') {
            return back()->with('error', 'Beneficiary is already active.');
        }

        DB::transaction(function () use ($beneficiary) {
            $old = $beneficiary->toArray();

            // Activate the beneficiary record
            $beneficiary->update([
                'status'          => 'active',
                'enrollment_date' => $beneficiary->enrollment_date ?? now()->toDateString(),
            ]);

            // Activate the linked portal user account
            if ($beneficiary->user) {
                $beneficiary->user->update(['is_active' => true]);
            }

            // Issue the QR card (first-time activation)
            $card = $this->cardService->issueCard($beneficiary, auth()->id());

            AuditLogService::log(
                'beneficiary_activated',
                $beneficiary,
                $old,
                ['status' => 'active', 'card_number' => $card->card_number],
                'Beneficiary application approved and card issued'
            );
        });

        return back()->with('success', 'Beneficiary activated. QR card issued and ready to download.');
    }

    public function issueCard(int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($id);
        $card        = $this->cardService->issueCard($beneficiary);

        AuditLogService::log('card_issued', $beneficiary, [], ['card_number' => $card->card_number], 'New ID card issued');

        return back()->with('success', "New card issued: {$card->card_number}. Download the PDF to print.");
    }

    /**
     * Batch issue cards for multiple beneficiaries at once.
     * Skips those who already have an active card.
     */
    public function batchIssueCards(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1|max:200',
            'ids.*' => 'integer|exists:beneficiaries,id',
        ]);

        $issued  = 0;
        $skipped = 0;
        $errors  = 0;

        $beneficiaries = Beneficiary::with(['user', 'cards'])
            ->whereIn('id', $request->ids)
            ->where('status', 'active')
            ->get();

        foreach ($beneficiaries as $beneficiary) {
            // Skip if already has an active card
            if ($beneficiary->cards->where('is_active', true)->count() > 0) {
                $skipped++;
                continue;
            }

            try {
                $card = $this->cardService->issueCard($beneficiary, auth()->id());
                AuditLogService::log(
                    'card_issued',
                    $beneficiary,
                    [],
                    ['card_number' => $card->card_number],
                    'Card issued via batch issuance'
                );
                $issued++;
            } catch (\Throwable $e) {
                $errors++;
                \Illuminate\Support\Facades\Log::error("Batch card issue failed for {$beneficiary->unique_id}: " . $e->getMessage());
            }
        }

        $msg = "Batch complete: {$issued} card(s) issued.";
        if ($skipped) $msg .= " {$skipped} skipped (already had a card).";
        if ($errors)  $msg .= " {$errors} failed — check logs.";

        return back()->with('success', $msg);
    }

    public function downloadCard(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $beneficiary = Beneficiary::with('card')->findOrFail($id);

        if (!$beneficiary->card_path || !Storage::disk('public')->exists($beneficiary->card_path)) {
            return back()->with('error', 'Card PDF not found. Please re-issue the card.');
        }

        $fullPath = Storage::disk('public')->path($beneficiary->card_path);
        AuditLogService::log('card_downloaded', $beneficiary, [], [], 'Card PDF downloaded');

        return response()->download($fullPath, "SECURE-4PS-Card-{$beneficiary->unique_id}.pdf");
    }

    public function cardPreview(int $id): Response
    {
        $beneficiary = Beneficiary::with(['card'])->findOrFail($id);
        $card        = $beneficiary->cards()->where('is_active', true)->latest()->first();

        // Build QR image as Base64 for Vue
        $qrBase64 = null;
        if ($card?->qr_code_image_path && Storage::disk('public')->exists($card->qr_code_image_path)) {
            $raw      = Storage::disk('public')->get($card->qr_code_image_path);
            $mime     = str_ends_with($card->qr_code_image_path, '.svg') ? 'image/svg+xml' : 'image/png';
            $qrBase64 = "data:{$mime};base64," . base64_encode($raw);
        }

        // Build photo as Base64 for Vue
        $photoBase64 = null;
        if ($beneficiary->photo_path && Storage::disk('public')->exists($beneficiary->photo_path)) {
            $ext         = pathinfo($beneficiary->photo_path, PATHINFO_EXTENSION);
            $photoBase64 = "data:image/{$ext};base64," . base64_encode(Storage::disk('public')->get($beneficiary->photo_path));
        }

        return Inertia::render('Superadmin/Beneficiaries/CardPreview', [
            'beneficiary'     => $beneficiary,
            'card'            => $card,
            'qrBase64'        => $qrBase64,
            'photoBase64'     => $photoBase64,
            'defaultPassword' => $card?->default_password_plain ?? '—',
        ]);
    }
}
