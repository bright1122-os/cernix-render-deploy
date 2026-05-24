@extends('layouts.admin-control')

@section('admin-title', 'Risk Intelligence')

@section('admin-content')
@php
    $summary = $intelligence['summary'] ?? [];
    $overview = $intelligence['risk_overview'] ?? [];
    $students = collect($intelligence['high_risk_students'] ?? []);
    $examiners = collect($intelligence['suspicious_examiners'] ?? []);
    $devices = collect($intelligence['suspicious_devices'] ?? [])->merge($intelligence['suspicious_ips'] ?? []);
    $observations = collect($intelligence['key_observations'] ?? []);
    $recommendations = collect($intelligence['recommendations'] ?? []);
    $departmentTrends = collect($intelligence['department_trends'] ?? []);
    $levelTrends = collect($intelligence['level_trends'] ?? []);
    $riskDistribution = $intelligence['risk_distribution'] ?? ['low' => 0, 'medium' => 0, 'high' => 0];
    $isPython = ($intelligence['source'] ?? 'live') === 'python';
@endphp

<style>
    .intel-page { display:grid; gap:16px; }
    .intel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:16px; }
    .intel-head h1 { margin:0; font-size:clamp(30px,5vw,44px); line-height:1; letter-spacing:-.06em; }
    .intel-head p { margin:8px 0 0; color:var(--ink-3); line-height:1.55; max-width:720px; }
    .intel-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .intel-source { display:inline-flex; width:fit-content; padding:6px 10px; border-radius:999px; background:{{ $isPython ? 'rgba(5,150,105,.12)' : 'rgba(180,83,9,.12)' }}; color:{{ $isPython ? 'var(--emerald)' : 'var(--amber)' }}; font-size:11px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    .intel-notice { border:1px solid var(--line); border-radius:16px; background:#fff; padding:12px 14px; color:var(--ink-2); line-height:1.55; }
    .intel-metrics { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); border:1px solid var(--line); border-radius:18px; overflow:hidden; background:#fff; }
    .intel-metrics .metric-cell { padding:13px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); min-width:0; }
    .intel-metrics .metric-cell:nth-child(2n) { border-right:0; }
    .intel-list { margin:0; padding-left:18px; color:var(--ink-2); line-height:1.65; }
    .intel-list li + li { margin-top:4px; }
    .intel-command { display:block; margin-top:8px; padding:10px 12px; border:1px solid var(--line); border-radius:12px; background:rgba(244,244,239,.72); color:var(--ink); font-size:12px; white-space:pre-wrap; overflow-wrap:anywhere; }
    .intel-table .admin-table { min-width:900px; }
    .risk-level { display:inline-flex; width:fit-content; padding:4px 8px; border-radius:999px; font-size:10px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; background:rgba(15,32,80,.08); color:var(--navy); }
    .risk-level.high { background:rgba(220,38,38,.12); color:var(--red); }
    .risk-level.medium { background:rgba(180,83,9,.12); color:var(--amber); }
    .risk-level.low { background:rgba(5,150,105,.12); color:var(--emerald); }
    .intel-help summary { cursor:pointer; font-weight:900; color:var(--ink); }
    @media (min-width:900px) {
        .intel-metrics { grid-template-columns:repeat(5,minmax(0,1fr)); }
        .intel-metrics .metric-cell, .intel-metrics .metric-cell:nth-child(2n) { border-right:1px solid var(--line); }
        .intel-metrics .metric-cell:nth-child(5n) { border-right:0; }
    }
    @media (max-width:640px) {
        .intel-head { display:block; }
        .intel-actions { justify-content:flex-start; margin-top:12px; }
        .intel-metrics .metric-cell { padding:12px; }
    }
</style>

<div class="intel-head">
    <div>
        <div class="cx-eyebrow">CERNIX Intelligence</div>
        <h1>Risk Intelligence</h1>
        <p>Python-assisted risk and verification insight for CERNIX exam access activity.</p>
    </div>
    <div class="intel-actions">
        <span class="intel-source">{{ $intelligence['source_label'] ?? 'Live Laravel summary' }}</span>
        <a class="admin-action ghost" href="{{ route('admin.intelligence') }}">Refresh Intelligence</a>
        <a class="admin-action ghost" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
    </div>
</div>

@if($intelligence['notice'] ?? null)
    <div class="intel-notice">
        {{ $intelligence['notice'] }}
        @if($intelligence['error'] ?? null)
            <br><strong>{{ $intelligence['error'] }}</strong>
        @endif
    </div>
@endif

<div class="intel-page">
    <section class="intel-metrics" aria-label="Intelligence summary">
        <div class="metric-cell"><span class="metric-label">Total Scans</span><b class="metric-value">{{ number_format($summary['total_scans'] ?? 0) }}</b></div>
        <div class="metric-cell"><span class="metric-label">Approved</span><b class="metric-value">{{ number_format($summary['approved_count'] ?? 0) }}</b></div>
        <div class="metric-cell"><span class="metric-label">Rejected</span><b class="metric-value">{{ number_format($summary['rejected_count'] ?? 0) }}</b></div>
        <div class="metric-cell"><span class="metric-label">Duplicate</span><b class="metric-value">{{ number_format($summary['duplicate_count'] ?? 0) }}</b></div>
        <div class="metric-cell"><span class="metric-label">Approval Rate</span><b class="metric-value">{{ number_format((float) ($summary['approval_rate'] ?? 0), 1) }}%</b></div>
        <div class="metric-cell"><span class="metric-label">Duplicate Rate</span><b class="metric-value">{{ number_format((float) ($summary['duplicate_rate'] ?? 0), 1) }}%</b></div>
        <div class="metric-cell"><span class="metric-label">Rejection Rate</span><b class="metric-value">{{ number_format((float) ($summary['rejection_rate'] ?? 0), 1) }}%</b></div>
        <div class="metric-cell"><span class="metric-label">Total Students</span><b class="metric-value">{{ number_format($summary['total_students'] ?? 0) }}</b></div>
        <div class="metric-cell"><span class="metric-label">Verified Payments</span><b class="metric-value">{{ number_format($summary['verified_payments'] ?? 0) }}</b></div>
        <div class="metric-cell"><span class="metric-label">Unused Tokens</span><b class="metric-value">{{ number_format($summary['unused_tokens'] ?? $summary['active_tokens'] ?? 0) }}</b></div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Risk Overview</h2><span>{{ $intelligence['status'] ?? 'Available' }}</span></div>
        <div class="admin-section-body">
            <div class="metric-strip">
                <div class="metric-cell"><span class="metric-label">High-risk Students</span><b class="metric-value">{{ number_format($overview['high_risk_students_count'] ?? 0) }}</b></div>
                <div class="metric-cell"><span class="metric-label">Suspicious Examiners</span><b class="metric-value">{{ number_format($overview['suspicious_examiners_count'] ?? 0) }}</b></div>
                <div class="metric-cell"><span class="metric-label">Suspicious Devices</span><b class="metric-value">{{ number_format($overview['suspicious_devices_count'] ?? 0) }}</b></div>
                <div class="metric-cell"><span class="metric-label">Suspicious IPs</span><b class="metric-value">{{ number_format($overview['suspicious_ips_count'] ?? 0) }}</b></div>
                <div class="metric-cell"><span class="metric-label">Duplicate Attempts</span><b class="metric-value">{{ number_format($overview['duplicate_attempts'] ?? 0) }}</b></div>
                <div class="metric-cell"><span class="metric-label">Rejected Attempts</span><b class="metric-value">{{ number_format($overview['rejected_attempts'] ?? 0) }}</b></div>
            </div>
            <p class="muted" style="margin:14px 0 0">Risk distribution: Low {{ $riskDistribution['low'] ?? 0 }}, Medium {{ $riskDistribution['medium'] ?? 0 }}, High {{ $riskDistribution['high'] ?? 0 }}.</p>
        </div>
    </section>

    <section class="admin-section intel-table">
        <div class="admin-section-head"><h2>Department / Level Trends</h2><span>Risk concentration</span></div>
        <div class="admin-section-body">
            <div class="admin-grid two">
                <div>
                    <h3 style="margin:0 0 10px;font-size:14px">Departments</h3>
                    @if($departmentTrends->isEmpty())
                        <div class="admin-empty">No department trend data available yet.</div>
                    @else
                        <div class="admin-table-wrap">
                            <table class="admin-table" style="min-width:520px">
                                <thead><tr><th>Department</th><th>Scans</th><th>Rejected</th><th>Duplicate</th><th>Risk</th></tr></thead>
                                <tbody>
                                    @foreach($departmentTrends->take(6) as $trend)
                                        <tr>
                                            <td>{{ $trend['label'] ?? 'Unknown' }}</td>
                                            <td class="mono">{{ $trend['total_scans'] ?? 0 }}</td>
                                            <td class="mono">{{ $trend['rejected_count'] ?? 0 }} <span class="muted">({{ number_format((float) ($trend['rejection_rate'] ?? 0), 1) }}%)</span></td>
                                            <td class="mono">{{ $trend['duplicate_count'] ?? 0 }} <span class="muted">({{ number_format((float) ($trend['duplicate_rate'] ?? 0), 1) }}%)</span></td>
                                            <td class="mono">{{ $trend['risk_score'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div>
                    <h3 style="margin:0 0 10px;font-size:14px">Levels</h3>
                    @if($levelTrends->isEmpty())
                        <div class="admin-empty">No level trend data available yet.</div>
                    @else
                        <div class="admin-table-wrap">
                            <table class="admin-table" style="min-width:520px">
                                <thead><tr><th>Level</th><th>Scans</th><th>Rejected</th><th>Duplicate</th><th>Risk</th></tr></thead>
                                <tbody>
                                    @foreach($levelTrends->take(6) as $trend)
                                        <tr>
                                            <td>{{ $trend['label'] ?? 'Unknown' }}</td>
                                            <td class="mono">{{ $trend['total_scans'] ?? 0 }}</td>
                                            <td class="mono">{{ $trend['rejected_count'] ?? 0 }} <span class="muted">({{ number_format((float) ($trend['rejection_rate'] ?? 0), 1) }}%)</span></td>
                                            <td class="mono">{{ $trend['duplicate_count'] ?? 0 }} <span class="muted">({{ number_format((float) ($trend['duplicate_rate'] ?? 0), 1) }}%)</span></td>
                                            <td class="mono">{{ $trend['risk_score'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Key Observations</h2><span>{{ $observations->count() }} items</span></div>
        <div class="admin-section-body">
            <ul class="intel-list">
                @forelse($observations as $observation)
                    <li>{{ $observation }}</li>
                @empty
                    <li>No high-risk student activity detected.</li>
                @endforelse
            </ul>
        </div>
    </section>

    <section class="admin-section intel-table">
        <div class="admin-section-head"><h2>High-risk Students</h2><span>{{ $students->count() }} records</span></div>
        <div class="admin-section-body">
            @if($students->isEmpty())
                <div class="admin-empty">No high-risk students detected from current records.</div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Matric No</th><th>Student Name</th><th>Department</th><th>Level</th><th>Risk Score</th><th>Risk Level</th><th>Reasons</th><th>Recommendation</th></tr></thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td class="mono">{{ $student['matric_no'] ?? '-' }}</td>
                                    <td>{{ $student['student_name'] ?? '-' }}</td>
                                    <td>{{ $student['department'] ?? '-' }}</td>
                                    <td>{{ $student['level'] ?? '-' }}</td>
                                    <td class="mono">{{ $student['score'] ?? 0 }}</td>
                                    <td><span class="risk-level {{ strtolower((string) ($student['risk_level'] ?? 'low')) }}">{{ $student['risk_level'] ?? 'low' }}</span></td>
                                    <td>{{ implode('; ', (array) ($student['reasons'] ?? [])) ?: '-' }}</td>
                                    <td>{{ $student['recommendation'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="admin-section intel-table">
        <div class="admin-section-head"><h2>Suspicious Examiners</h2><span>{{ $examiners->count() }} records</span></div>
        <div class="admin-section-body">
            @if($examiners->isEmpty())
                <div class="admin-empty">No suspicious examiner activity detected.</div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Examiner</th><th>Total Scans</th><th>Approved</th><th>Rejected</th><th>Duplicate</th><th>Suspicious Score</th><th>Reasons</th><th>Recommendation</th></tr></thead>
                        <tbody>
                            @foreach($examiners as $examiner)
                                <tr>
                                    <td>{{ $examiner['examiner_name'] ?? ('Examiner #' . ($examiner['examiner_id'] ?? '-')) }}</td>
                                    <td class="mono">{{ $examiner['total_scans'] ?? 0 }}</td>
                                    <td class="mono">{{ $examiner['approved_count'] ?? 0 }}</td>
                                    <td class="mono">{{ $examiner['rejected_count'] ?? 0 }}</td>
                                    <td class="mono">{{ $examiner['duplicate_count'] ?? 0 }}</td>
                                    <td class="mono">{{ $examiner['suspicious_score'] ?? 0 }}</td>
                                    <td>{{ implode('; ', (array) ($examiner['reasons'] ?? [])) ?: '-' }}</td>
                                    <td>{{ $examiner['recommendation'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="admin-section intel-table">
        <div class="admin-section-head"><h2>Suspicious Devices / IPs</h2><span>{{ $devices->count() }} records</span></div>
        <div class="admin-section-body">
            @if($devices->isEmpty())
                <div class="admin-empty">No device/IP risk pattern available yet.</div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Identifier</th><th>Type</th><th>Total Scans</th><th>Unique Students</th><th>Unique Examiners</th><th>Rejected</th><th>Duplicate</th><th>Risk Level</th><th>Recommendation</th></tr></thead>
                        <tbody>
                            @foreach($devices as $device)
                                <tr>
                                    <td class="mono">{{ $device['identifier'] ?? '-' }}</td>
                                    <td>{{ $device['type'] ?? '-' }}</td>
                                    <td class="mono">{{ $device['total_scans'] ?? 0 }}</td>
                                    <td class="mono">{{ $device['unique_students'] ?? 0 }}</td>
                                    <td class="mono">{{ $device['unique_examiners'] ?? 0 }}</td>
                                    <td class="mono">{{ $device['rejected_count'] ?? 0 }}</td>
                                    <td class="mono">{{ $device['duplicate_count'] ?? 0 }}</td>
                                    <td><span class="risk-level {{ strtolower((string) ($device['risk_level'] ?? 'low')) }}">{{ $device['risk_level'] ?? 'low' }}</span></td>
                                    <td>{{ $device['recommendation'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Recommendations</h2><span>{{ $recommendations->count() }} items</span></div>
        <div class="admin-section-body">
            <ul class="intel-list">
                @forelse($recommendations as $recommendation)
                    <li>{{ $recommendation }}</li>
                @empty
                    <li>Generate the Python-enhanced report for deeper device and student risk scoring.</li>
                @endforelse
            </ul>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Report Status</h2><span>{{ $intelligence['source_label'] ?? 'Live Laravel summary' }}</span></div>
        <div class="admin-section-body admin-info-list">
            <div class="admin-info-row"><span class="admin-label">Report source</span><b class="admin-value">{{ $intelligence['source_label'] ?? 'Live Laravel summary' }}</b></div>
            <div class="admin-info-row"><span class="admin-label">Last generated</span><b class="admin-value">{{ $intelligence['generated_at'] ?? 'Generated live for this request' }}</b></div>
            <div class="admin-info-row"><span class="admin-label">JSON path</span><b class="admin-value mono">{{ $intelligence['json_path'] ?? 'storage/app/risk-analysis/risk_report.json' }}</b></div>
            <div class="admin-info-row"><span class="admin-label">HTML report path</span><b class="admin-value mono">{{ ($intelligence['html_exists'] ?? false) ? ($intelligence['html_path'] ?? 'storage/app/risk-analysis/risk_report.html') : 'Not generated' }}</b></div>
            <details class="intel-help">
                <summary>Generate Python report</summary>
                <p class="muted">Run these from the Laravel project root when you want a Python-enhanced report. The web page still works without them.</p>
                <code class="intel-command">php artisan cernix:export-risk-data</code>
                <code class="intel-command">python python_services/risk_analyzer/analyze.py storage/app/risk-analysis/scan_logs.json storage/app/risk-analysis/risk_report.json --html storage/app/risk-analysis/risk_report.html</code>
                <code class="intel-command">php artisan cernix:run-risk-analysis</code>
            </details>
        </div>
    </section>
</div>
@endsection
