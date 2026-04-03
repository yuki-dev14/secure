<?php

namespace App\Http\Controllers\ComplianceVerifier;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\ComplianceRecord;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_for_review'   => Beneficiary::active()->count(),
            'verified_today'     => ComplianceRecord::whereDate('created_at', today())->count(),
            'compliant'          => Beneficiary::compliant()->count(),
            'non_compliant'      => Beneficiary::active()->where('is_compliant', false)->count(),
            'pending_edu'        => Beneficiary::active()
                ->whereHas('familyMembers', fn($q) => $q->where('is_school_age', true))
                ->whereDoesntHave('complianceRecords', fn($q) => $q->where('edu_attendance_compliant', true))
                ->count(),
            'pending_health'     => Beneficiary::active()
                ->whereHas('familyMembers', fn($q) => $q->where('is_under_five', true))
                ->whereDoesntHave('complianceRecords', fn($q) => $q->where('health_compliant', true))
                ->count(),
            'recent_records'     => ComplianceRecord::with(['beneficiary', 'verifier'])
                ->latest()->limit(8)->get(),
        ];

        return Inertia::render('ComplianceVerifier/Dashboard', compact('stats'));
    }
}
