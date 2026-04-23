<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Event Completion Report</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #1e293b;
        background: #fff;
        padding: 0.5in 0.5in 0.6in;
    }

    /* ── Header ─────────────────────────────────────────────────────────── */
    .report-header {
        background: linear-gradient(135deg, #065f46 0%, #10b981 100%);
        color: white;
        padding: 16pt 20pt;
        border-radius: 8pt;
        margin-bottom: 16pt;
    }
    .report-header .agency   { font-size: 7pt; opacity: 0.8; letter-spacing: 0.5pt; text-transform: uppercase; }
    .report-header h1        { font-size: 15pt; font-weight: bold; margin: 3pt 0 2pt; }
    .report-header .subtitle { font-size: 9pt; opacity: 0.9; }
    .report-header .meta     { font-size: 8pt; opacity: 0.75; margin-top: 3pt; }
    .report-header .badge    { display: inline-block; background: rgba(255,255,255,0.2); padding: 2pt 8pt; border-radius: 20pt; font-size: 7.5pt; margin-top: 5pt; }

    /* ── Event info box ──────────────────────────────────────────────────── */
    .event-info {
        background: #f0fdf4;
        border: 1pt solid #bbf7d0;
        border-radius: 8pt;
        padding: 12pt 14pt;
        margin-bottom: 14pt;
        display: flex;
        gap: 20pt;
    }
    .event-info .info-block { flex: 1; }
    .event-info .info-label { font-size: 7pt; color: #065f46; text-transform: uppercase; letter-spacing: 0.4pt; margin-bottom: 2pt; }
    .event-info .info-value { font-size: 9pt; font-weight: bold; color: #1e293b; }
    .event-info .info-sub   { font-size: 7.5pt; color: #64748b; margin-top: 1pt; }

    /* Status badge */
    .status-badge {
        display: inline-block;
        padding: 3pt 10pt;
        border-radius: 20pt;
        font-size: 8pt;
        font-weight: bold;
        background: #d1fae5;
        color: #065f46;
        border: 1pt solid #6ee7b7;
    }

    /* ── KPI cards ───────────────────────────────────────────────────────── */
    .kpi-row {
        display: flex;
        gap: 8pt;
        margin-bottom: 14pt;
    }
    .kpi-card {
        flex: 1;
        background: #f8fafc;
        border: 1pt solid #e2e8f0;
        border-radius: 8pt;
        padding: 10pt 8pt;
        text-align: center;
    }
    .kpi-card .kpi-label { font-size: 6.5pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.3pt; margin-bottom: 3pt; }
    .kpi-card .kpi-value { font-size: 16pt; font-weight: bold; color: #1e293b; }
    .kpi-card .kpi-value.green  { color: #16a34a; }
    .kpi-card .kpi-value.red    { color: #dc2626; }
    .kpi-card .kpi-value.purple { color: #7c3aed; }
    .kpi-card .kpi-value.amber  { color: #d97706; }
    .kpi-card .kpi-sub { font-size: 6.5pt; color: #94a3b8; margin-top: 2pt; }

    /* Claim rate progress bar */
    .progress-section {
        background: #f8fafc;
        border: 1pt solid #e2e8f0;
        border-radius: 8pt;
        padding: 10pt 14pt;
        margin-bottom: 14pt;
    }
    .progress-section .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: 8pt;
        margin-bottom: 5pt;
    }
    .progress-track {
        width: 100%;
        height: 10pt;
        background: #e2e8f0;
        border-radius: 20pt;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        border-radius: 20pt;
        background: linear-gradient(90deg, #10b981, #6366f1);
    }

    /* ── Section header ──────────────────────────────────────────────────── */
    .section { margin-bottom: 16pt; page-break-inside: avoid; }
    .section-title {
        font-size: 9.5pt;
        font-weight: bold;
        color: #065f46;
        border-bottom: 2pt solid #10b981;
        padding-bottom: 3pt;
        margin-bottom: 8pt;
    }

    /* ── Data table ──────────────────────────────────────────────────────── */
    table.data-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.data-table th {
        background: #065f46;
        color: white;
        padding: 5pt 8pt;
        text-align: left;
        font-size: 7.5pt;
        letter-spacing: 0.3pt;
    }
    table.data-table th.right { text-align: right; }
    table.data-table th.center { text-align: center; }
    table.data-table td { padding: 4pt 8pt; border-bottom: 0.5pt solid #e2e8f0; color: #374151; }
    table.data-table td.right { text-align: right; font-weight: bold; }
    table.data-table td.center { text-align: center; }
    table.data-table tr:nth-child(even) td { background: #f8fafc; }
    table.data-table tr:last-child td { border-bottom: none; }
    table.data-table tfoot td { background: #f0fdf4; font-weight: bold; border-top: 1.5pt solid #6ee7b7; }

    /* ── Inline bar ──────────────────────────────────────────────────────── */
    .bar-row { display: flex; align-items: center; gap: 6pt; margin-bottom: 4pt; }
    .bar-label { font-size: 7.5pt; color: #374151; width: 120pt; flex-shrink: 0; text-align: right; padding-right: 4pt; }
    .bar-track { flex: 1; background: #f1f5f9; border-radius: 20pt; height: 9pt; overflow: hidden; }
    .bar-fill  { height: 100%; border-radius: 20pt; background: linear-gradient(90deg, #10b981, #6ee7b7); }
    .bar-value { font-size: 7.5pt; font-weight: bold; color: #374151; width: 50pt; flex-shrink: 0; }

    /* ── Fraud box ───────────────────────────────────────────────────────── */
    .no-fraud {
        background: #f0fdf4;
        border: 1pt solid #bbf7d0;
        border-radius: 6pt;
        padding: 10pt 14pt;
        font-size: 8.5pt;
        color: #065f46;
    }
    .fraud-table th { background: #7f1d1d; }

    /* ── Two-col ─────────────────────────────────────────────────────────── */
    .two-col { display: flex; gap: 14pt; }
    .two-col .col { flex: 1; }

    /* ── Footer ──────────────────────────────────────────────────────────── */
    .report-footer {
        margin-top: 20pt;
        padding-top: 6pt;
        border-top: 0.5pt solid #cbd5e1;
        font-size: 7pt;
        color: #94a3b8;
        display: flex;
        justify-content: space-between;
    }
</style>
</head>
<body>

<!-- ── Report Header ──────────────────────────────────────────────────────── -->
<div class="report-header">
    <div class="agency">Republic of the Philippines — DSWD Lipa City, Batangas · SECURE 4Ps System</div>
    <h1>Distribution Event Completion Report</h1>
    <div class="subtitle">{{ $event->title }}</div>
    <div class="meta">{{ $event->period }} &nbsp;·&nbsp; {{ $event->venue }}</div>
    <div class="badge">Generated: {{ $generatedAt }}</div>
</div>

<!-- ── Event Details Box ──────────────────────────────────────────────────── -->
<div class="event-info">
    <div class="info-block">
        <div class="info-label">Period</div>
        <div class="info-value">{{ $event->period }}</div>
        <div class="info-sub">
            {{ \Carbon\Carbon::parse($event->period_start)->format('M d, Y') }}
            → {{ \Carbon\Carbon::parse($event->period_end)->format('M d, Y') }}
        </div>
    </div>
    <div class="info-block">
        <div class="info-label">Venue</div>
        <div class="info-value">{{ $event->venue }}</div>
        @if($event->venue_address)
        <div class="info-sub">{{ $event->venue_address }}</div>
        @endif
    </div>
    <div class="info-block">
        <div class="info-label">Status</div>
        <div class="info-value"><span class="status-badge">✓ Completed</span></div>
        <div class="info-sub">{{ $event->office?->name ?? 'All Offices' }}</div>
    </div>
    <div class="info-block">
        <div class="info-label">Created By</div>
        <div class="info-value">{{ $event->creator?->name ?? '—' }}</div>
        <div class="info-sub">{{ \Carbon\Carbon::parse($event->created_at)->format('M d, Y') }}</div>
    </div>
</div>

<!-- ── KPI Summary Row ────────────────────────────────────────────────────── -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-label">Active Beneficiaries</div>
        <div class="kpi-value">{{ number_format($summary['total_beneficiaries']) }}</div>
        <div class="kpi-sub">enrolled in program</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Grants Computed</div>
        <div class="kpi-value amber">{{ number_format($summary['computed']) }}</div>
        <div class="kpi-sub">eligible + ineligible</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Eligible</div>
        <div class="kpi-value green">{{ number_format($summary['eligible']) }}</div>
        <div class="kpi-sub">have completion record</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Claims Processed</div>
        <div class="kpi-value green">{{ number_format($summary['claimed']) }}</div>
        <div class="kpi-sub">{{ $summary['claim_rate'] }}% of eligible</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Unclaimed</div>
        <div class="kpi-value red">{{ number_format($summary['unclaimed']) }}</div>
        <div class="kpi-sub">did not claim</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total Released</div>
        <div class="kpi-value purple">₱{{ number_format($summary['total_released'], 0) }}</div>
        <div class="kpi-sub">cash disbursed</div>
    </div>
</div>

<!-- ── Claim Rate Progress ────────────────────────────────────────────────── -->
<div class="progress-section">
    <div class="progress-label">
        <span>Claim Rate: <strong>{{ $summary['claim_rate'] }}%</strong> of eligible beneficiaries have claimed</span>
        <span>{{ $summary['claimed'] }} of {{ $summary['eligible'] }} eligible</span>
    </div>
    <div class="progress-track">
        <div class="progress-fill" style="width:{{ $summary['claim_rate'] }}%"></div>
    </div>
</div>

<!-- ── Claimed Beneficiaries Table ───────────────────────────────────────── -->
<div class="section">
    <div class="section-title">✅ Claimed Beneficiaries ({{ $claimed->count() }})</div>
    @if($claimed->isEmpty())
    <p style="color:#94a3b8; font-size:8pt; text-align:center; padding:8pt;">No claims were recorded for this event.</p>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Unique ID</th>
                <th>Beneficiary</th>
                <th>Barangay</th>
                <th class="center">Claimed By</th>
                <th>Released By</th>
                <th>Date & Time</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($claimed as $i => $dist)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td style="font-family: monospace; font-size:7.5pt;">{{ $dist->beneficiary?->unique_id ?? '—' }}</td>
                <td>{{ $dist->beneficiary?->full_name ?? '—' }}</td>
                <td>{{ $dist->beneficiary?->barangay ?? '—' }}</td>
                <td class="center">
                    {{ $dist->claimed_by_type === 'proxy' ? 'Via Proxy' : 'Self' }}
                </td>
                <td style="font-size:7.5pt;">{{ $dist->releasedBy?->name ?? '—' }}</td>
                <td style="font-size:7.5pt;">{{ $dist->claimed_at ? \Carbon\Carbon::parse($dist->claimed_at)->format('M d, Y g:i A') : '—' }}</td>
                <td class="right">₱{{ number_format($dist->amount_released, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" style="padding: 5pt 8pt; font-size:8pt;">TOTAL RELEASED</td>
                <td class="right" style="font-size:9pt; color:#065f46;">₱{{ number_format($summary['total_released'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>

<!-- ── Two-col: Barangay Breakdown + Ineligible ───────────────────────────── -->
<div class="two-col" style="page-break-before: always;">

    <!-- Barangay Breakdown -->
    <div class="col section">
        <div class="section-title">🏘 Claims per Barangay</div>
        @if($barangayBreakdown->isEmpty())
        <p style="color:#94a3b8; font-size:8pt;">No data.</p>
        @else
        @foreach($barangayBreakdown as $row)
        <div class="bar-row">
            <div class="bar-label">{{ Str::limit($row['barangay'], 20) }}</div>
            <div class="bar-track">
                <div class="bar-fill"
                    style="width:{{ $maxBarangayCount > 0 ? round(($row['count'] / $maxBarangayCount) * 100) : 0 }}%">
                </div>
            </div>
            <div class="bar-value">
                {{ $row['count'] }}
                <span style="color:#94a3b8; font-size:6.5pt;"> / ₱{{ number_format($row['total_amount'], 0) }}</span>
            </div>
        </div>
        @endforeach
        @endif
    </div>

    <!-- Ineligible List -->
    <div class="col section">
        <div class="section-title">❌ Ineligible Beneficiaries ({{ $ineligible->count() }})</div>
        <p style="font-size:7.5pt; color:#64748b; margin-bottom:6pt;">
            These beneficiaries had no verified completion record for {{ $event->period }}.
        </p>
        @if($ineligible->isEmpty())
        <p style="color:#16a34a; font-size:8pt; padding:6pt; background:#f0fdf4; border-radius:4pt; border:1pt solid #bbf7d0;">
            ✓ All computed beneficiaries were eligible.
        </p>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Unique ID</th>
                    <th>Name</th>
                    <th>Barangay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ineligible as $calc)
                <tr>
                    <td style="font-family:monospace; font-size:7pt;">{{ $calc->beneficiary?->unique_id ?? '—' }}</td>
                    <td style="font-size:7.5pt;">{{ $calc->beneficiary?->full_name ?? '—' }}</td>
                    <td style="font-size:7.5pt;">{{ $calc->beneficiary?->barangay ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<!-- ── Double-Claim Fraud Flags ───────────────────────────────────────────── -->
<div class="section">
    <div class="section-title">🚨 Double-Claim Attempts for This Event ({{ $doubleClaims->count() }})</div>
    @if($doubleClaims->isEmpty())
    <div class="no-fraud">
        ✓ No double-claim attempts were detected for this event. No fraud flags recorded.
    </div>
    @else
    <table class="data-table fraud-table">
        <thead>
            <tr>
                <th>Detected At</th>
                <th>Beneficiary</th>
                <th>Unique ID</th>
                <th>Barangay</th>
                <th>Detected By (Officer)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doubleClaims as $dc)
            <tr>
                <td style="font-size:7.5pt;">{{ $dc['detected_at'] }}</td>
                <td>{{ $dc['beneficiary_name'] }}</td>
                <td style="font-family:monospace; font-size:7pt;">{{ $dc['beneficiary_uid'] }}</td>
                <td>{{ $dc['barangay'] }}</td>
                <td>{{ $dc['officer'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@if($event->notes)
<!-- ── Notes ──────────────────────────────────────────────────────────────── -->
<div class="section">
    <div class="section-title">📋 Event Notes</div>
    <p style="font-size:8.5pt; color:#374151; line-height:1.5; background:#f8fafc; padding:8pt; border-radius:6pt; border:0.5pt solid #e2e8f0;">
        {{ $event->notes }}
    </p>
</div>
@endif

<!-- ── Footer ─────────────────────────────────────────────────────────────── -->
<div class="report-footer">
    <span>SECURE 4Ps — System for Eligibility Checking, Unified Records &amp; Evaluation · DSWD Lipa City</span>
    <span>CONFIDENTIAL — For authorized DSWD personnel only</span>
</div>

</body>
</html>
