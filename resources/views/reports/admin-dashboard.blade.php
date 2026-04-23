<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard Report</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #1e293b;
        background: #ffffff;
        padding: 0.5in 0.5in 0.6in;
    }

    /* ── Header ─────────────────────────────────────────────────────────── */
    .report-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: white;
        padding: 18pt 20pt;
        border-radius: 8pt;
        margin-bottom: 18pt;
    }
    .report-header .agency {
        font-size: 7pt;
        opacity: 0.8;
        letter-spacing: 0.5pt;
        text-transform: uppercase;
    }
    .report-header h1 {
        font-size: 16pt;
        font-weight: bold;
        margin: 4pt 0 2pt;
    }
    .report-header .meta {
        font-size: 8pt;
        opacity: 0.8;
    }
    .report-header .badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 2pt 8pt;
        border-radius: 20pt;
        font-size: 7.5pt;
        margin-top: 6pt;
    }

    /* ── KPI Summary Cards ───────────────────────────────────────────────── */
    .kpi-row {
        display: flex;
        gap: 10pt;
        margin-bottom: 18pt;
    }
    .kpi-card {
        flex: 1;
        background: #f8fafc;
        border: 1pt solid #e2e8f0;
        border-radius: 8pt;
        padding: 12pt 10pt;
        text-align: center;
    }
    .kpi-card .kpi-label {
        font-size: 7pt;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        margin-bottom: 4pt;
    }
    .kpi-card .kpi-value {
        font-size: 18pt;
        font-weight: bold;
        color: #1e3a8a;
    }
    .kpi-card .kpi-value.green  { color: #16a34a; }
    .kpi-card .kpi-value.red    { color: #dc2626; }
    .kpi-card .kpi-value.purple { color: #7c3aed; }
    .kpi-card .kpi-value.amber  { color: #d97706; }

    /* ── Section header ──────────────────────────────────────────────────── */
    .section {
        margin-bottom: 20pt;
        page-break-inside: avoid;
    }
    .section-title {
        font-size: 10pt;
        font-weight: bold;
        color: #1e3a8a;
        border-bottom: 2pt solid #3b82f6;
        padding-bottom: 4pt;
        margin-bottom: 10pt;
        display: flex;
        align-items: center;
        gap: 5pt;
    }
    .section-subtitle {
        font-size: 7.5pt;
        color: #64748b;
        margin-bottom: 8pt;
    }

    /* ── Bar chart ───────────────────────────────────────────────────────── */
    .bar-row {
        display: flex;
        align-items: center;
        gap: 8pt;
        margin-bottom: 5pt;
    }
    .bar-label {
        font-size: 7.5pt;
        color: #374151;
        width: 130pt;
        flex-shrink: 0;
        text-align: right;
        padding-right: 4pt;
    }
    .bar-track {
        flex: 1;
        background: #f1f5f9;
        border-radius: 20pt;
        height: 10pt;
        overflow: hidden;
    }
    .bar-fill {
        height: 100%;
        border-radius: 20pt;
    }
    .bar-fill.blue   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .bar-fill.green  { background: linear-gradient(90deg, #16a34a, #4ade80); }
    .bar-fill.purple { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
    .bar-fill.red    { background: linear-gradient(90deg, #dc2626, #f87171); }
    .bar-fill.amber  { background: linear-gradient(90deg, #d97706, #fbbf24); }
    .bar-value {
        font-size: 7.5pt;
        font-weight: bold;
        color: #374151;
        width: 45pt;
        flex-shrink: 0;
    }

    /* ── Monthly claims table ────────────────────────────────────────────── */
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
    }
    table.data-table th {
        background: #1e3a8a;
        color: white;
        padding: 5pt 8pt;
        text-align: left;
        font-size: 7.5pt;
        letter-spacing: 0.3pt;
    }
    table.data-table th.right { text-align: right; }
    table.data-table td {
        padding: 4pt 8pt;
        border-bottom: 0.5pt solid #e2e8f0;
        color: #374151;
    }
    table.data-table td.right { text-align: right; font-weight: bold; }
    table.data-table tr:nth-child(even) td { background: #f8fafc; }
    table.data-table tr:last-child td { border-bottom: none; }

    /* ── Trend indicator ─────────────────────────────────────────────────── */
    .trend-bar-track {
        display: inline-block;
        width: 50pt;
        height: 6pt;
        background: #e2e8f0;
        border-radius: 3pt;
        vertical-align: middle;
        margin-left: 4pt;
    }
    .trend-bar-fill {
        display: inline-block;
        height: 6pt;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
        border-radius: 3pt;
    }

    /* ── Double claims highlight ─────────────────────────────────────────── */
    .fraud-alert-box {
        background: #fef2f2;
        border: 1pt solid #fca5a5;
        border-radius: 6pt;
        padding: 10pt 12pt;
        margin-bottom: 10pt;
    }
    .fraud-alert-box .fraud-count {
        font-size: 20pt;
        font-weight: bold;
        color: #dc2626;
    }
    .fraud-alert-box .fraud-label {
        font-size: 8pt;
        color: #991b1b;
        margin-top: 2pt;
    }

    /* ── Footer ─────────────────────────────────────────────────────────── */
    .report-footer {
        margin-top: 24pt;
        padding-top: 8pt;
        border-top: 0.5pt solid #cbd5e1;
        font-size: 7pt;
        color: #94a3b8;
        display: flex;
        justify-content: space-between;
    }

    /* ── Two-column layout ───────────────────────────────────────────────── */
    .two-col {
        display: flex;
        gap: 14pt;
    }
    .two-col .col {
        flex: 1;
    }
</style>
</head>
<body>

<!-- ── Report Header ──────────────────────────────────────────────────────── -->
<div class="report-header">
    <div class="agency">Republic of the Philippines — DSWD Lipa City, Batangas · SECURE 4Ps System</div>
    <h1>Admin Dashboard Report</h1>
    <div class="meta">Pantawid Pamilyang Pilipino Program (4Ps) · Claim Statistics &amp; Analytics</div>
    <div class="badge">Generated: {{ $generatedAt }}</div>
</div>

<!-- ── KPI Summary ────────────────────────────────────────────────────────── -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-label">Active Beneficiaries</div>
        <div class="kpi-value">{{ number_format($summary['total_beneficiaries']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total Claims</div>
        <div class="kpi-value green">{{ number_format($summary['total_claims']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total Disbursed</div>
        <div class="kpi-value purple">₱{{ number_format($summary['total_amount'], 0) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Double-Claim Flags</div>
        <div class="kpi-value red">{{ number_format($summary['total_double_claims']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Barangays Covered</div>
        <div class="kpi-value amber">{{ $summary['unique_barangays'] }}</div>
    </div>
</div>

<!-- ── Claims Over Time ───────────────────────────────────────────────────── -->
<div class="section">
    <div class="section-title">📈 Claims Over Time — Last 12 Months</div>
    <div class="section-subtitle">Monthly count of successful cash grant claims and total amount disbursed</div>
    @php $maxMonthTotal = $claimsByMonth->max('total') ?: 1; @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th>Month</th>
                <th>Claims</th>
                <th>Volume</th>
                <th class="right">Amount Disbursed</th>
            </tr>
        </thead>
        <tbody>
            @foreach($claimsByMonth as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>
                    <div style="display:flex; align-items:center; gap:5pt;">
                        <span>{{ $row['total'] }}</span>
                        <div class="trend-bar-track">
                            <div class="trend-bar-fill"
                                style="width:{{ $maxMonthTotal > 0 ? round(($row['total'] / $maxMonthTotal) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </td>
                <td>
                    @if($row['total'] > 0)
                        <span style="color:#16a34a; font-size:7pt;">▲ Active</span>
                    @else
                        <span style="color:#94a3b8; font-size:7pt;">— No activity</span>
                    @endif
                </td>
                <td class="right">₱{{ number_format($row['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- ── Two-Column Section ─────────────────────────────────────────────────── -->
<div class="two-col">

    <!-- Claims per Barangay -->
    <div class="col section">
        <div class="section-title">🏘 Claims per Barangay</div>
        <div class="section-subtitle">Top barangays by number of claims processed</div>
        @foreach($claimsByBarangay as $row)
        <div class="bar-row">
            <div class="bar-label">{{ Str::limit($row->barangay ?? '—', 22) }}</div>
            <div class="bar-track">
                <div class="bar-fill blue"
                    style="width:{{ $maxClaims > 0 ? round(($row->total_claims / $maxClaims) * 100) : 0 }}%"></div>
            </div>
            <div class="bar-value">{{ $row->total_claims }}</div>
        </div>
        @endforeach
        @if($claimsByBarangay->isEmpty())
        <p style="color:#94a3b8; font-size:8pt; text-align:center; padding:10pt;">No claim data available.</p>
        @endif
    </div>

    <!-- Beneficiaries per Barangay -->
    <div class="col section">
        <div class="section-title">👥 Beneficiaries per Barangay</div>
        <div class="section-subtitle">Active enrolled households per barangay</div>
        @foreach($beneficiariesByBarangay as $row)
        <div class="bar-row">
            <div class="bar-label">{{ Str::limit($row->barangay ?? '—', 22) }}</div>
            <div class="bar-track">
                <div class="bar-fill green"
                    style="width:{{ $maxBeneficiaries > 0 ? round(($row->total / $maxBeneficiaries) * 100) : 0 }}%"></div>
            </div>
            <div class="bar-value">{{ $row->total }}</div>
        </div>
        @endforeach
        @if($beneficiariesByBarangay->isEmpty())
        <p style="color:#94a3b8; font-size:8pt; text-align:center; padding:10pt;">No beneficiary data available.</p>
        @endif
    </div>
</div>

<!-- ── Beneficiaries Claiming per Barangay ────────────────────────────────── -->
<div class="section" style="page-break-before: always;">
    <div class="section-title">✅ Beneficiaries Claiming per Barangay</div>
    <div class="section-subtitle">Number of unique beneficiaries who have claimed at least once, grouped by barangay</div>
    @foreach($claimingByBarangay as $row)
    <div class="bar-row">
        <div class="bar-label">{{ Str::limit($row->barangay ?? '—', 22) }}</div>
        <div class="bar-track">
            <div class="bar-fill purple"
                style="width:{{ $maxClaimers > 0 ? round(($row->claimers / $maxClaimers) * 100) : 0 }}%"></div>
        </div>
        <div class="bar-value">{{ $row->claimers }}</div>
    </div>
    @endforeach
    @if($claimingByBarangay->isEmpty())
    <p style="color:#94a3b8; font-size:8pt; text-align:center; padding:10pt;">No claiming data available.</p>
    @endif
</div>

<!-- ── Double-Claim Attempts ──────────────────────────────────────────────── -->
<div class="section">
    <div class="section-title">🚨 Double-Claim Fraud Flags per Distribution Event</div>

    <div style="display:flex; gap:12pt; margin-bottom:10pt;">
        <div class="fraud-alert-box" style="flex:1;">
            <div class="fraud-count">{{ number_format($totalDoubleClaims) }}</div>
            <div class="fraud-label">Total double-claim attempts recorded system-wide</div>
        </div>
        <div style="flex:2;">
            <div class="section-subtitle" style="margin-top:8pt;">
                Double-claim attempts are automatically blocked and flagged by the system.
                Each entry below represents how many times a beneficiary attempted to claim
                a grant they had already received within the same distribution event.
            </div>
        </div>
    </div>

    @if($doubleClaims->isEmpty())
    <p style="color:#16a34a; font-size:9pt; padding:8pt; background:#f0fdf4; border-radius:6pt; border:1pt solid #bbf7d0;">
        ✓ No double-claim attempts have been recorded. The system is clean.
    </p>
    @else
    @foreach($doubleClaims as $row)
    <div class="bar-row">
        <div class="bar-label" style="width:150pt;">
            <span style="font-size:7pt;">{{ Str::limit($row['event_title'], 28) }}</span>
            @if($row['period'])
            <br><span style="font-size:6pt; color:#94a3b8;">{{ $row['period'] }}</span>
            @endif
        </div>
        <div class="bar-track">
            <div class="bar-fill red"
                style="width:{{ $maxAttempts > 0 ? round(($row['attempts'] / $maxAttempts) * 100) : 0 }}%"></div>
        </div>
        <div class="bar-value" style="color:#dc2626;">{{ $row['attempts'] }}</div>
    </div>
    @endforeach
    @endif
</div>

<!-- ── Footer ─────────────────────────────────────────────────────────────── -->
<div class="report-footer">
    <span>SECURE 4Ps — System for Eligibility Checking, Unified Records &amp; Evaluation · DSWD Lipa City</span>
    <span>CONFIDENTIAL — For authorized DSWD personnel only</span>
</div>

</body>
</html>
