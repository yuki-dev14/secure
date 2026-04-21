<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Proxy;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    /**
     * Store a new proxy for a beneficiary.
     * Maximum 2 active proxies enforced here.
     */
    public function store(Request $request, int $beneficiaryId): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);

        // Enforce max 2 active proxies
        $activeCount = $beneficiary->proxies()->count();
        if ($activeCount >= 2) {
            return back()->with('error', 'This beneficiary already has 2 authorized proxies. Remove one before adding another.');
        }

        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'middle_name'  => 'nullable|string|max:100',
            'suffix'       => 'nullable|string|max:10',
            'birthdate'    => 'required|date|before:today',
            'sex'          => 'required|in:male,female',
            'relationship' => 'required|in:spouse,child,parent,sibling,grandchild,grandparent,in-law,neighbor,other',
            'contact_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string|max:255',
            'valid_id_type'=> 'nullable|string|max:100',
            'valid_id_number'=> 'nullable|string|max:100',
        ]);

        $proxy = $beneficiary->proxies()->create(array_merge($validated, [
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'is_active'   => true,
        ]));

        AuditLogService::log(
            'proxy_added',
            $proxy,
            [],
            $proxy->toArray(),
            "Proxy '{$proxy->full_name}' added for beneficiary {$beneficiary->unique_id}"
        );

        return back()->with('success', "Proxy '{$proxy->full_name}' added and approved successfully.");
    }

    /**
     * Update proxy details.
     */
    public function update(Request $request, int $beneficiaryId, int $proxyId): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);
        $proxy       = Proxy::where('beneficiary_id', $beneficiary->id)->findOrFail($proxyId);

        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'middle_name'  => 'nullable|string|max:100',
            'suffix'       => 'nullable|string|max:10',
            'birthdate'    => 'required|date|before:today',
            'sex'          => 'required|in:male,female',
            'relationship' => 'required|in:spouse,child,parent,sibling,grandchild,grandparent,in-law,neighbor,other',
            'contact_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string|max:255',
            'valid_id_type'  => 'nullable|string|max:100',
            'valid_id_number'=> 'nullable|string|max:100',
        ]);

        $old = $proxy->toArray();
        $proxy->update($validated);

        AuditLogService::updated($proxy, $old, $proxy->fresh()->toArray());

        return back()->with('success', 'Proxy record updated successfully.');
    }

    /**
     * Deactivate (soft-remove) a proxy — keeps audit record.
     */
    public function destroy(int $beneficiaryId, int $proxyId): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);
        $proxy       = Proxy::where('beneficiary_id', $beneficiary->id)->findOrFail($proxyId);

        AuditLogService::log(
            'proxy_removed',
            $proxy,
            $proxy->toArray(),
            [],
            "Proxy '{$proxy->full_name}' removed from beneficiary {$beneficiary->unique_id}"
        );

        $proxy->update(['is_active' => false]);
        $proxy->delete(); // soft delete

        return back()->with('success', "Proxy '{$proxy->full_name}' has been removed.");
    }

    /**
     * Toggle approval status — can revoke or re-approve a proxy.
     */
    public function toggleApproval(int $beneficiaryId, int $proxyId): RedirectResponse
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);
        $proxy       = Proxy::where('beneficiary_id', $beneficiary->id)->withTrashed()->findOrFail($proxyId);

        $proxy->update([
            'is_approved' => !$proxy->is_approved,
            'approved_by' => $proxy->is_approved ? null : auth()->id(),
            'approved_at' => $proxy->is_approved ? null : now(),
        ]);

        $status = $proxy->fresh()->is_approved ? 'approved' : 'revoked';
        AuditLogService::log(
            'proxy_approval_changed',
            $proxy,
            [],
            ['status' => $status],
            "Proxy '{$proxy->full_name}' approval {$status}"
        );

        return back()->with('success', "Proxy authorization {$status} successfully.");
    }
}
