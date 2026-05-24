@extends('layouts.admin-control')

@section('admin-title', 'Risk Intelligence')

@section('admin-content')
@php
    $data = $report['data'] ?? [];
    $summary = $data['daily_summary'] ?? $data;
    $students = collect($data['high_risk_students'] ?? []);
    $examiners = collect($data['suspicious_examiners'] ?? []);
    $devices = collect($data['suspicious_devices'] ?? [])->merge($data['suspicious_ips'] ?? []);
    $observations = collect($summary['key_observations'] ?? []);
    $recommendations = collect($data['recommendations'] ?? $summary['recommendations'] ?? []);
    $riskDistribution = $data['risk_distribution'] ?? $data['risk_summary'] ?? [];
    $highRiskStudents = $students->filter(fn ($student) => strtolower((string) ($student['risk_level'] ?? '')) === 'high')->count();
@endphp

<style>
    .intel-page { display: grid; gap: 16px; }
    .intel-metrics { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); border:1px solid var(--line); border-radius:18px; overflow:hidden; background:#fff; }
    .intel-metrics .metric-cell { padding:14px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); min-width:0; }
    .intel-metrics .metric-cell:nth-child(2n) { border-right:0; }
    .intel-list { margin:0; padding-left:18px; color:var(--ink-2); line-height:1.65; }
    .intel-list li + li { margin-top:4px; }
    .intel-command { display:block; margin-top:8px; padding:10px 12px; border:1px solid var(--line); border-radius:12px; background:rgba(244,244,239,.72); color:var(--ink); font-size:12px; white-space:pre-wrap; overflow-wrap:anywhere; }
    .intel-table .admin-table { min-width:860px; }
    .risk-level { display:inline-flex; width:fit-content; padding:4px 8px; border-radius:999px; font-size:10px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; background:rgba(15,32,80,.08); color:var(--navy); }
    .risk-level.high { background:rgba(220,38,38,.12); color:var(--red); }
    .risk-level.medium { background:rgba(180,83,9,.12); color:var(--amber); }
    .risk-level.low { background:rgba(5,150,105,.12); color:var(--emerald); }
    @media (min-width:900px) {
        .intel-metrics { grid-template-columns:repeat(4,minmax(0,1fr)); }
        .intel-metrics .metric-cell, .intel-metrics .metric-cell:nth-child(2n) { border-right:1px solid var(--line); }
        .intel-metrics .metric-cell:nth-child(4n) { border-right:0; }
    }
</style>

<div class="admin-page-head">
    <div>
        <div class="cx-eyebrow">Python Intelligence Module</div>
        <h1>Risk Intelligence</h1>
        <p>Admin-facing risk summary generated from exported CERNIX scan, audit, payment, device, and examiner activity. Laravel renders JSON safely; the Python HTML report is stored only as an optional artifact.</p>
    </div>
    <a class="admin-action ghost" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>

@if(! ($report['exists'] ?? false))
    <section class="admin-section">
        <div class="admin-section-head">
            <h2>Risk intelligence report has not been generated yet.</h2>
            <span>{{ $report['status'] ?? 'Not generated' }}</span>
        </div>
        <div class="admin-section-body">
            @if($report['error'] ?? null)
                <p class="admin-status red">{{ $report['error'] }}</p>
            @endif
            <p class="muted">Generate the export and run the Python analyzer from the Laravel project root:</p>
            <code class="intel-command">php artisan cernix:export-risk-data</code>
            <code class="intel-command">python python_services/risk_analyzer/analyze.py storage/app/risk-analysis/scan_logs.json storage/app/risk-analysis/risk_report.json --html storage/app/risk-analysis/risk_report.html</code>
            <code class="intel-command">php artisan cernix:run-risk-analysis</code>
        </div>
    </section>
@else
    <div class="intel-page">
        <section class="intel-metrics" aria-label="Intelligence summary">
            <div class="metric-cell"><span class="metric-label">Total Scans</span><b class="metric-value">{{ number_format($data['total_scans'] ?? 0) }}</b></div>
            <div class="metric-cell"><span class="metric-label">Approved</span><b class="metric-value">{{ number_format($data['approved_count'] ?? 0) }}</b></div>
            <div class="metric-cell"><span class="metric-label">Rejected</span><b class="metric-value">{{ number_format($data['rejected_count'] ?? 0) }}</b></div>
            <div class="metric-cell"><span class="metric-label">Duplicate</span><b class="metric-value">{{ number_format($data['duplicate_count'] ?? 0) }}</b></div>
            <div class="metric-cell"><span class="metric-label">Approval Rate</span><b class="metric-value">{{ number_format((float) ($data['approval_rate'] ?? 0), 1) }}%</b></div>
            <div class="metric-cell"><span class="metric-label">Duplicate Rate</span><b class="metric-value">{{ number_format((float) ($data['duplicate_rate'] ?? 0), 1) }}%</b></div>
            <div class="metric-cell"><span class="metric-label">Rejection Rate</span><b class="metric-value">{{ number_format((float) ($data['rejection_rate'] ?? 0), 1) }}%</b></div>
            <div class="metric-cell"><span class="metric-label">Overall Risk</span><b class="metric-value">{{ \Illuminate\Support\Str::headline((string) ($data['risk_summary']['overall_level'] ?? 'low')) }}</b></div>
        </section>

        <section class="admin-section">
            <div class="admin-section-head"><h2>Risk Overview</h2><span>Report distribution</span></div>
            <div class="admin-section-body">
                <div class="metric-strip">
                    <div class="metric-cell"><span class="metric-label">High-risk Students</span><b class="metric-value">{{ number_format($highRiskStudents) }}</b></div>
                    <div class="metric-cell"><span class="metric-label">Suspicious Examiners</span><b class="metric-value">{{ number_format($examiners->count()) }}</b></div>
                    <div class="metric-cell"><span class="metric-label">Suspicious Devices</span><b class="metric-value">{{ number_format(collect($data['suspicious_devices'] ?? [])->count()) }}</b></div>
                    <div class="metric-cell"><span class="metric-label">Suspicious IPs</span><b class="metric-value">{{ number_format(collect($data['suspicious_ips'] ?? [])->count()) }}</b></div>
                </div>
                <p class="muted" style="margin:14px 0 0">Risk distribution: Low {{ $riskDistribution['low'] ?? 0 }}, Medium {{ $riskDistribution['medium'] ?? 0 }}, High {{ $riskDistribution['high'] ?? 0 }}.</p>
            </div>
        </section>

        <section class="admin-section">
            <div class="admin-section-head"><h2>Key Observations</h2><span>{{ $observations->count() }} items</span></div>
            <div class="admin-section-body">
                @if($observations->isEmpty())
                    <div class="admin-empty">No key observations were generated.</div>
                @else
                    <ul class="intel-list">
                        @foreach($observations as $observation)
                            <li>{{ $observation }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section class="admin-section intel-table">
            <div class="admin-section-head"><h2>High-risk Students</h2><span>{{ $students->count() }} records</span></div>
            <div class="admin-section-body">
                @if($students->isEmpty())
                    <div class="admin-empty">No high or medium risk student activity detected.</div>
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
                    <div class="admin-empty">No suspicious device or IP patterns detected.</div>
                @else
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Identifier</th><th>Type</th><th>Total Scans</th><th>Unique Students</th><th>Unique Examiners</th><th>Risk Level</th><th>Reasons</th><th>Recommendation</th></tr></thead>
                            <tbody>
                                @foreach($devices as $device)
                                    <tr>
                                        <td class="mono">{{ $device['identifier'] ?? '-' }}</td>
                                        <td>{{ $device['type'] ?? '-' }}</td>
                                        <td class="mono">{{ $device['total_scans'] ?? 0 }}</td>
                                        <td class="mono">{{ $device['unique_students'] ?? 0 }}</td>
                                        <td class="mono">{{ $device['unique_examiners'] ?? 0 }}</td>
                                        <td><span class="risk-level {{ strtolower((string) ($device['risk_level'] ?? 'low')) }}">{{ $device['risk_level'] ?? 'low' }}</span></td>
                                        <td>{{ implode('; ', (array) ($device['reasons'] ?? [])) ?: '-' }}</td>
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
                @if($recommendations->isEmpty())
                    <div class="admin-empty">No recommendations were generated.</div>
                @else
                    <ul class="intel-list">
                        @foreach($recommendations as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section class="admin-section">
            <div class="admin-section-head"><h2>Report Metadata</h2><span>Python module status</span></div>
            <div class="admin-section-body admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Generated at</span><b class="admin-value">{{ $report['generated_at'] ?? 'Unknown' }}</b></div>
                <div class="admin-info-row"><span class="admin-label">Source file</span><b class="admin-value mono">{{ $jsonPath }}</b></div>
                <div class="admin-info-row"><span class="admin-label">Optional HTML artifact</span><b class="admin-value mono">{{ ($report['html_exists'] ?? false) ? $htmlPath : 'Not generated' }}</b></div>
                <div class="admin-info-row"><span class="admin-label">Python module status</span><b class="admin-value">{{ $report['status'] ?? 'Available' }}</b></div>
            </div>
        </section>
    </div>
@endif
@endsection
