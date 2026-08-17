<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\CashGrantCalculation;
use App\Models\DistributionEvent;
use App\Models\FamilyMember;
use App\Services\CashGrantCalculatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // ─── Summary page (report hub) ────────────────────────────────────────────

    public function index(): Response
    {
        $activeBeneficiaries = Beneficiary::where('status', 'active')->count();
        $totalBeneficiaries  = Beneficiary::count();

        // School-age children eligible for education grant
        $schoolAgeChildren = FamilyMember::where('is_school_age', true)
            ->where('is_active', true)
            ->whereHas('beneficiary', fn($q) => $q->where('status', 'active'))
            ->count();

        // Calculate expected grants for the next bimonthly period (2 months)
        $months = 2;

        // Health: ₱750 × 2 months × active households
        $healthTotal = CashGrantCalculatorService::HEALTH_GRANT_PER_MONTH * $months * $activeBeneficiaries;

        // Education: estimate by school-age children (capped at 3 per household)
        // Use average grant rate as approximation
        $eduChildren = FamilyMember::where('is_school_age', true)
            ->where('is_active', true)
            ->whereHas('beneficiary', fn($q) => $q->where('status', 'active'))
            ->get();

        $eduTotal = 0;
        foreach ($eduChildren as $child) {
            $rate = match($child->education_level) {
                'senior_high' => CashGrantCalculatorService::SENIOR_HIGH_GRANT_PER_MONTH,
                'junior_high' => CashGrantCalculatorService::JUNIOR_HIGH_GRANT_PER_MONTH,
                'elementary'  => CashGrantCalculatorService::ELEMENTARY_GRANT_PER_MONTH,
                default       => 0,
            };
            $eduTotal += $rate * $months;
        }

        // Rice: ₱600 × 2 months × active households
        $riceTotal = CashGrantCalculatorService::RICE_SUBSIDY_PER_MONTH * $months * $activeBeneficiaries;

        $expectedGrant = $healthTotal + $eduTotal + $riceTotal;

        // Next period label
        $nextPeriod = $this->getNextPeriodLabel();

        // Barangay count
        $barangayCount = Beneficiary::where('status', 'active')
            ->distinct('barangay')
            ->count('barangay');

        $summary = [
            'beneficiaries' => [
                'total'  => $totalBeneficiaries,
                'active' => $activeBeneficiaries,
            ],
            'expected_grant'     => $expectedGrant,
            'next_period_label'  => $nextPeriod,
            'school_age_children'=> $schoolAgeChildren,
            'barangay_count'     => $barangayCount,
            'breakdown' => [
                'health'    => $healthTotal,
                'education' => $eduTotal,
                'rice'      => $riceTotal,
            ],
        ];

        return Inertia::render('Superadmin/Reports/Index', compact('summary'));
    }

    // ─── Beneficiaries report ─────────────────────────────────────────────────

    public function beneficiaries(Request $request): Response
    {
        $query = Beneficiary::with(['office', 'card'])
            ->withCount('familyMembers')
            ->when($request->barangay,  fn ($q) => $q->where('barangay', $request->barangay))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->latest();

        $beneficiaries = $query->paginate(50)->withQueryString();
        $barangays     = Beneficiary::distinct()->orderBy('barangay')->pluck('barangay');

        return Inertia::render('Superadmin/Reports/Beneficiaries', compact('beneficiaries', 'barangays'));
    }

    public function exportBeneficiaries(Request $request): StreamedResponse
    {
        $rows = Beneficiary::with('office')
            ->withCount('familyMembers')
            ->when($request->barangay, fn ($q) => $q->where('barangay', $request->barangay))
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->latest()->get();

        return $this->streamCsv('beneficiaries_report', [
            'Unique ID', 'Last Name', 'First Name', 'Middle Name',
            'Barangay', 'Status', 'Family Members',
            'Listahanan ID', 'Contact', 'Registered',
        ], $rows->map(fn ($b) => [
            $b->unique_id, $b->last_name, $b->first_name, $b->middle_name ?? '',
            $b->barangay, $b->status,
            $b->family_members_count,
            $b->listahanan_id ?? '', $b->contact_number ?? '',
            $b->created_at?->format('Y-m-d'),
        ])->toArray());
    }

    // ─── Grants report ────────────────────────────────────────────────────────

    public function grants(Request $request): Response
    {
        $query = CashGrantCalculation::with(['beneficiary', 'distributionEvent'])
            ->when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))
            ->latest();

        $grants  = $query->paginate(50)->withQueryString();
        $events  = DistributionEvent::orderByDesc('created_at')->get(['id', 'title', 'period']);

        $totals = [
            'total_health'    => CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('health_grant_amount'),
            'total_education' => CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('education_grant_total'),
            'total_rice'      => CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('rice_subsidy_amount'),
            'grand_total'     => CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('total_grant_amount'),
        ];

        return Inertia::render('Superadmin/Reports/Grants', compact('grants', 'events', 'totals'));
    }

    public function exportGrants(Request $request): StreamedResponse
    {
        $rows = CashGrantCalculation::with(['beneficiary', 'distributionEvent'])
            ->when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))
            ->latest()->get();

        return $this->streamCsv('grants_report', [
            'Beneficiary ID', 'Event', 'Period', 'Months Covered',
            'Health Grant', 'Education Grant', 'Rice Subsidy', 'Total',
            'Elementary Children', 'Junior High', 'Senior High', 'Computed At',
        ], $rows->map(fn ($g) => [
            $g->beneficiary?->unique_id ?? '',
            $g->distributionEvent?->title ?? '',
            $g->distributionEvent?->period ?? '',
            $g->months_covered,
            number_format($g->health_grant_amount, 2),
            number_format($g->education_grant_total, 2),
            number_format($g->rice_subsidy_amount, 2),
            number_format($g->total_grant_amount, 2),
            $g->elementary_children_count ?? 0,
            $g->junior_high_children_count ?? 0,
            $g->senior_high_children_count ?? 0,
            $g->computed_at?->format('Y-m-d') ?? $g->created_at?->format('Y-m-d'),
        ])->toArray());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getNextPeriodLabel(): string
    {
        $month = (int) now()->format('m');
        $year  = now()->year;

        $bimonthly = [
            1 => 'P1 (Jan–Feb)', 2 => 'P1 (Jan–Feb)',
            3 => 'P2 (Mar–Apr)', 4 => 'P2 (Mar–Apr)',
            5 => 'P3 (May–Jun)', 6 => 'P3 (May–Jun)',
            7 => 'P4 (Jul–Aug)', 8 => 'P4 (Jul–Aug)',
            9 => 'P5 (Sep–Oct)', 10 => 'P5 (Sep–Oct)',
            11 => 'P6 (Nov–Dec)', 12 => 'P6 (Nov–Dec)',
        ];

        // Find the next period (not the current one)
        $nextMonth = $month + 2;
        if ($nextMonth > 12) {
            $nextMonth -= 12;
            $year++;
        }

        return "{$year} {$bimonthly[$nextMonth]}";
    }

    private function streamCsv(string $name, array $headers, array $rows): StreamedResponse
    {
        $filename = $name . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
