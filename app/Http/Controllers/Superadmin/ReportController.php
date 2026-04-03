<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CashGrantDistribution;
use App\Models\ComplianceRecord;
use App\Models\DistributionEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // ─── Summary page (report hub) ────────────────────────────────────────────

    public function index(): Response
    {
        $summary = [
            'beneficiaries' => [
                'total'      => Beneficiary::count(),
                'active'     => Beneficiary::where('status', 'active')->count(),
                'compliant'  => Beneficiary::where('is_compliant', true)->count(),
            ],
            'distributions' => [
                'total'      => CashGrantDistribution::count(),
                'claimed'    => CashGrantDistribution::where('status', 'claimed')->count(),
                'total_released' => CashGrantDistribution::where('status', 'claimed')->sum('amount_released'),
            ],
            'compliance' => [
                'total'          => ComplianceRecord::count(),
                'fully_compliant' => ComplianceRecord::where('is_fully_compliant', true)->count(),
            ],
            'events' => [
                'total'    => DistributionEvent::count(),
                'ongoing'  => DistributionEvent::whereIn('status', ['ongoing', 'upcoming'])->count(),
                'completed'=> DistributionEvent::where('status', 'completed')->count(),
            ],
            'audit' => [
                'total'  => AuditLog::count(),
                'today'  => AuditLog::whereDate('created_at', today())->count(),
                'fraud'  => AuditLog::where('event', 'double_claim_attempt')->count(),
            ],
        ];

        $recentDistributions = CashGrantDistribution::with(['beneficiary', 'distributionEvent'])
            ->latest()->limit(5)->get();

        $barangayBreakdown = Beneficiary::select('barangay', DB::raw('count(*) as total'), DB::raw('sum(case when is_compliant then 1 else 0 end) as compliant'))
            ->groupBy('barangay')->orderByDesc('total')->limit(10)->get();

        return Inertia::render('Superadmin/Reports/Index', compact('summary', 'recentDistributions', 'barangayBreakdown'));
    }

    // ─── Beneficiaries report ─────────────────────────────────────────────────

    public function beneficiaries(Request $request): Response
    {
        $query = Beneficiary::with(['office', 'card'])
            ->withCount('familyMembers')
            ->when($request->barangay,  fn ($q) => $q->where('barangay', $request->barangay))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->when($request->compliant !== null && $request->compliant !== '', fn ($q) =>
                $q->where('is_compliant', (bool) $request->compliant))
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
            'Barangay', 'Status', 'Compliant', 'Family Members',
            'Listahanan ID', 'Contact', 'Registered',
        ], $rows->map(fn ($b) => [
            $b->unique_id, $b->last_name, $b->first_name, $b->middle_name ?? '',
            $b->barangay, $b->status, $b->is_compliant ? 'Yes' : 'No',
            $b->family_members_count,
            $b->listahanan_id ?? '', $b->contact_number ?? '',
            $b->created_at?->format('Y-m-d'),
        ])->toArray());
    }

    // ─── Compliance report ────────────────────────────────────────────────────

    public function compliance(Request $request): Response
    {
        $query = ComplianceRecord::with(['beneficiary', 'verifier'])
            ->when($request->period,    fn ($q) => $q->where('period', $request->period))
            ->when($request->compliant !== null && $request->compliant !== '', fn ($q) =>
                $q->where('is_fully_compliant', (bool) $request->compliant))
            ->latest('created_at');

        $records = $query->paginate(50)->withQueryString();
        $periods = ComplianceRecord::distinct()->orderByDesc('period')->pluck('period');

        return Inertia::render('Superadmin/Reports/Compliance', compact('records', 'periods'));
    }

    public function exportCompliance(Request $request): StreamedResponse
    {
        $rows = ComplianceRecord::with(['beneficiary', 'verifier'])
            ->when($request->period, fn ($q) => $q->where('period', $request->period))
            ->latest('created_at')->get();

        return $this->streamCsv('compliance_report', [
            'Beneficiary ID', 'Period', 'Education %', 'Edu Compliant',
            'Health Compliant', 'FDS Compliant', 'Overall Compliant',
            'Override', 'Verified By', 'Remarks', 'Date',
        ], $rows->map(fn ($r) => [
            $r->beneficiary?->unique_id ?? $r->beneficiary_id,
            $r->period,
            $r->edu_attendance_rate ?? '—',
            $r->edu_attendance_compliant ? 'Yes' : 'No',
            $r->health_compliant ? 'Yes' : 'No',
            $r->fds_compliant ? 'Yes' : 'No',
            $r->is_fully_compliant ? 'Yes' : 'No',
            $r->compliance_override ? 'Yes' : 'No',
            $r->verifier?->name ?? '—',
            $r->override_remarks ?? '',
            $r->created_at?->format('Y-m-d'),
        ])->toArray());
    }

    // ─── Distributions report ─────────────────────────────────────────────────

    public function distributions(Request $request): Response
    {
        $query = CashGrantDistribution::with(['beneficiary', 'distributionEvent', 'releasedBy'])
            ->when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->latest();

        $distributions = $query->paginate(50)->withQueryString();
        $events        = DistributionEvent::orderByDesc('created_at')->get(['id', 'title', 'period']);

        $totals = [
            'count'    => CashGrantDistribution::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->count(),
            'claimed'  => CashGrantDistribution::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->where('status', 'claimed')->count(),
            'released' => (float) CashGrantDistribution::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->where('status', 'claimed')->sum('amount_released'),
        ];

        return Inertia::render('Superadmin/Reports/Distributions', compact('distributions', 'events', 'totals'));
    }

    public function exportDistributions(Request $request): StreamedResponse
    {
        $rows = CashGrantDistribution::with(['beneficiary', 'distributionEvent', 'releasedBy'])
            ->when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->latest()->get();

        return $this->streamCsv('distributions_report', [
            'Transaction ID', 'Beneficiary ID', 'Event', 'Period',
            'Amount Released', 'Status', 'Claimed By', 'Proxy ID',
            'Released By', 'Claim Date',
        ], $rows->map(fn ($d) => [
            $d->transaction_id ?? $d->id,
            $d->beneficiary?->unique_id ?? '',
            $d->distributionEvent?->title ?? '',
            $d->distributionEvent?->period ?? '',
            number_format($d->amount_released, 2),
            $d->status,
            $d->claimed_by_type,
            $d->proxy_id ?? '',
            $d->releasedBy?->name ?? '',
            $d->claimed_at?->format('Y-m-d H:i') ?? '',
        ])->toArray());
    }

    // ─── Grants report ────────────────────────────────────────────────────────

    public function grants(Request $request): Response
    {
        $query = \App\Models\CashGrantCalculation::with(['beneficiary', 'distributionEvent'])
            ->when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))
            ->latest();

        $grants  = $query->paginate(50)->withQueryString();
        $events  = DistributionEvent::orderByDesc('created_at')->get(['id', 'title', 'period']);

        $totals = [
            'total_health'    => \App\Models\CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('health_grant_amount'),
            'total_education' => \App\Models\CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('education_grant_total'),
            'total_rice'      => \App\Models\CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('rice_subsidy_amount'),
            'grand_total'     => \App\Models\CashGrantCalculation::when($request->event_id, fn ($q) => $q->where('distribution_event_id', $request->event_id))->sum('total_grant_amount'),
        ];

        return Inertia::render('Superadmin/Reports/Grants', compact('grants', 'events', 'totals'));
    }

    public function exportGrants(Request $request): StreamedResponse
    {
        $rows = \App\Models\CashGrantCalculation::with(['beneficiary', 'distributionEvent'])
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

    // ─── CSV helper ──────────────────────────────────────────────────────────

    private function streamCsv(string $name, array $headers, array $rows): StreamedResponse
    {
        $filename = $name . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
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
