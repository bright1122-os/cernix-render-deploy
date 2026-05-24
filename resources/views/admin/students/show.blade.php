@extends('layouts.admin-control')

@section('admin-title', 'Student Trace')

@section('admin-content')
@php
    $photo = $student->photo_path ? url('/photo-thumb/' . collect(explode('/', str_replace('\\', '/', ltrim($student->photo_path, '/'))))->map(fn($p) => rawurlencode($p))->implode('/')) : null;
    $readiness = collect([
        ['label' => 'Student record found', 'ok' => true],
        ['label' => 'Payment verified', 'ok' => (bool) $payment],
        ['label' => 'QR issued', 'ok' => (bool) $token],
        ['label' => 'Timetable assigned', 'ok' => $timetableCount > 0],
        ['label' => 'Exam pass ready', 'ok' => (bool) ($payment && $token)],
    ]);
@endphp

<div class="admin-page-head">
    <div>
        <div class="cx-eyebrow">Student Trace</div>
        <h1>{{ $student->full_name }}</h1>
        <p class="mono">{{ $student->matric_no }}</p>
    </div>
    <a class="admin-action ghost" href="{{ route('admin.students') }}">Back to Students</a>
</div>

<div class="admin-grid two">
    <section class="admin-section">
        <div class="admin-section-head"><h2>Identity and Access</h2></div>
        <div class="admin-section-body">
            <div style="display:flex;gap:16px;align-items:center;margin-bottom:16px">
                <x-student-photo :student="$student" size="admin-detail" />
                <div>
                    <b style="font-size:20px">{{ $student->full_name }}</b>
                    <div class="mono muted">{{ $student->matric_no }}</div>
                    <div class="muted">{{ $student->dept_name ?? 'Department unavailable' }} - {{ $student->level ?? 'Not available' }} level</div>
                </div>
            </div>
            <div class="admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Faculty</span><span class="admin-value">{{ $student->faculty ?? 'Not available' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Active Session</span><span class="admin-value">{{ $student->semester ?? 'Session' }} - {{ $student->academic_year ?? 'Not available' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Payment</span><span class="admin-value">{{ $payment ? 'Verified - ' . $payment->rrr_number : 'Not verified' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">QR Token</span><span class="admin-value mono">{{ $token ? Str::limit($token->token_id, 18) . ' - ' . $token->status : 'Not issued' }}</span></div>
            </div>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Readiness</h2><span>{{ $readiness->where('ok', true)->count() }}/5 complete</span></div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                @foreach($readiness as $item)
                    <div class="admin-info-row" style="grid-template-columns:1fr auto;align-items:center">
                        <span class="admin-value" style="margin:0;font-size:14px">{{ $item['label'] }}</span>
                        <span class="admin-status {{ $item['ok'] ? 'green' : 'amber' }}">{{ $item['ok'] ? 'Available' : 'Missing' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>

<div class="admin-grid two" style="margin-top:16px">
    <section class="admin-section">
        <div class="admin-section-head"><h2>Scan Summary</h2></div>
        <div class="admin-section-body">
            <div class="metric-strip">
                <div class="metric-cell"><span class="metric-label">Total</span><span class="metric-value">{{ $scanHistory->count() }}</span></div>
                <div class="metric-cell"><span class="metric-label">Approved</span><span class="metric-value">{{ $scanCounts['APPROVED'] ?? 0 }}</span></div>
                <div class="metric-cell"><span class="metric-label">Rejected</span><span class="metric-value">{{ $scanCounts['REJECTED'] ?? 0 }}</span></div>
                <div class="metric-cell"><span class="metric-label">Duplicate</span><span class="metric-value">{{ $scanCounts['DUPLICATE'] ?? 0 }}</span></div>
            </div>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Timeline</h2></div>
        <div class="admin-section-body">
            @if($timeline->count())
                <div class="admin-timeline">
                    @foreach($timeline as $event)
                        <div class="timeline-item">
                            <div class="timeline-dot">T</div>
                            <div class="timeline-card"><b>{{ $event['label'] }}</b><span>{{ $event['meta'] }} | {{ $event['time'] }}</span></div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="admin-empty">No trace activity is available yet.</div>
            @endif
        </div>
    </section>
</div>

<section class="admin-section" style="margin-top:16px">
    <div class="admin-section-head"><h2>Scan History</h2><span>{{ $scanHistory->count() }} recent records</span></div>
    <div class="admin-section-body">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Time</th><th>Decision</th><th>Examiner</th><th>Token</th><th>IP/Device</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($scanHistory as $row)
                        <tr>
                            <td class="mono">{{ $row->timestamp }}</td>
                            <td><span class="admin-status {{ $row->decision === 'APPROVED' ? 'green' : ($row->decision === 'DUPLICATE' ? 'amber' : 'red') }}">{{ $row->decision }}</span></td>
                            <td>{{ $row->examiner_name ?? 'Examiner unavailable' }}</td>
                            <td class="mono safe">{{ Str::limit($row->token_id, 18) }}</td>
                            <td class="safe">{{ $row->ip_address ?? 'Not available' }} · {{ $row->device_fp ?? 'Not available' }}</td>
                            <td><a class="admin-action ghost" href="{{ route('admin.scan-logs.show', $row->log_id) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="admin-empty">No scan history for this student yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@include('admin.partials.notes', ['entityType' => 'student', 'entityId' => $student->matric_no, 'notes' => $notes ?? collect()])
@endsection
