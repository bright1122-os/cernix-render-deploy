@extends('layouts.admin-control')

@section('admin-title', 'Student Trace')

@section('admin-content')
@php
    $photo = $student->photo_path ? url('/photo-thumb/' . collect(explode('/', str_replace('\\', '/', ltrim($student->photo_path, '/'))))->map(fn($p) => rawurlencode($p))->implode('/')) : null;
    $paymentPayload = $payment ? json_decode((string) $payment->remita_response, true) : [];
    $paymentStatus = $payment ? ($paymentPayload['status'] ?? 'Verified') : 'Not recorded yet';
    $paymentSource = $payment ? ($paymentPayload['source'] ?? (str_starts_with(strtoupper((string) $payment->rrr_number), 'TEST-') ? 'Demo' : 'Remita')) : 'Not recorded yet';
    $maskedRrr = $payment?->rrr_number
        ? (str_starts_with(strtoupper((string) $payment->rrr_number), 'TEST-')
            ? 'TEST-****'
            : str_repeat('*', max(strlen((string) $payment->rrr_number) - 4, 0)) . substr((string) $payment->rrr_number, -4))
        : 'Not recorded yet';
    $tokenReference = $token ? substr((string) $token->token_id, 0, 8) . '...' . substr((string) $token->token_id, -4) : 'Not issued';
    $totalScans = (int) collect($scanCounts)->sum();
    $readiness = collect([
        ['label' => 'Student record found', 'ok' => true],
        ['label' => 'Payment verified', 'ok' => (bool) $payment],
        ['label' => 'QR issued', 'ok' => (bool) $token],
        ['label' => 'Timetable assigned', 'ok' => $timetableCount > 0],
        ['label' => 'Exam pass ready', 'ok' => (bool) ($payment && $token)],
    ]);
@endphp

<style>
    .student-detail-hero { display:flex; gap:16px; align-items:center; min-width:0; }
    .student-detail-hero > div { min-width:0; }
    .student-detail-name { display:block; font-size:clamp(20px,4vw,28px); line-height:1.05; letter-spacing:-.03em; overflow-wrap:anywhere; }
    .student-detail-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    @media (max-width:640px) {
        .student-detail-hero { align-items:flex-start; }
        .student-detail-actions { justify-content:flex-start; }
    }
</style>

<div class="admin-page-head">
    <div>
        <div class="cx-eyebrow">Student Trace</div>
        <h1>{{ $student->full_name }}</h1>
        <p class="mono">{{ $student->matric_no }}</p>
    </div>
    <div class="student-detail-actions">
        @if($token)
            <a class="admin-action ghost" href="{{ route('admin.student-trace', ['q' => $student->matric_no]) }}">Open Trace Search</a>
        @endif
        <a class="admin-action ghost" href="{{ route('admin.students') }}">Back to Students</a>
    </div>
</div>

<div class="admin-grid two">
    <section class="admin-section">
        <div class="admin-section-head"><h2>Identity and Access</h2></div>
        <div class="admin-section-body">
            <div class="student-detail-hero" style="margin-bottom:16px">
                <x-student-photo :student="$student" size="admin-detail" />
                <div>
                    <b class="student-detail-name">{{ $student->full_name }}</b>
                    <div class="mono muted">{{ $student->matric_no }}</div>
                    <div class="muted">{{ $student->dept_name ?? 'Department unavailable' }} - {{ $student->level ?? 'Not available' }} level</div>
                </div>
            </div>
            <div class="admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Faculty</span><span class="admin-value">{{ $student->faculty ?? 'Not available' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Department</span><span class="admin-value">{{ $student->dept_name ?? 'Not recorded yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Level</span><span class="admin-value">{{ $student->level ?? 'Not recorded yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Active Session</span><span class="admin-value">{{ $student->semester ?? 'Session' }} - {{ $student->academic_year ?? 'Not available' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Registered</span><span class="admin-value">{{ $student->created_at ?? 'Not recorded yet' }}</span></div>
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
        <div class="admin-section-head"><h2>Payment</h2><span class="admin-status {{ $payment ? 'green' : 'amber' }}">{{ $payment ? 'Verified' : 'Pending' }}</span></div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Provider / Source</span><span class="admin-value">{{ Str::headline((string) $paymentSource) }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Reference</span><span class="admin-value mono">{{ $maskedRrr }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Status</span><span class="admin-value">{{ $paymentStatus }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Amount Declared</span><span class="admin-value">{{ $payment ? '₦' . number_format((float) $payment->amount_declared, 2) : 'Not recorded yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Amount Confirmed</span><span class="admin-value">{{ $payment ? '₦' . number_format((float) $payment->amount_confirmed, 2) : 'Not recorded yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Verified At</span><span class="admin-value">{{ $payment->verified_at ?? 'Not recorded yet' }}</span></div>
            </div>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Exam Access</h2><span class="admin-status {{ $token ? 'green' : 'amber' }}">{{ $token ? $token->status : 'Missing' }}</span></div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Token Reference</span><span class="admin-value mono">{{ $tokenReference }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Issued At</span><span class="admin-value">{{ $token->issued_at ?? 'Not recorded yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Used At</span><span class="admin-value">{{ $token->used_at ?? 'Not used yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Timetable Entries</span><span class="admin-value">{{ number_format($timetableCount) }}</span></div>
            </div>
        </div>
    </section>
</div>

<div class="admin-grid two" style="margin-top:16px">
    <section class="admin-section">
        <div class="admin-section-head"><h2>Scan Summary</h2></div>
        <div class="admin-section-body">
            <div class="metric-strip">
                <div class="metric-cell"><span class="metric-label">Total</span><span class="metric-value">{{ $totalScans }}</span></div>
                <div class="metric-cell"><span class="metric-label">Approved</span><span class="metric-value">{{ $scanCounts['APPROVED'] ?? 0 }}</span></div>
                <div class="metric-cell"><span class="metric-label">Rejected</span><span class="metric-value">{{ $scanCounts['REJECTED'] ?? 0 }}</span></div>
                <div class="metric-cell"><span class="metric-label">Duplicate</span><span class="metric-value">{{ $scanCounts['DUPLICATE'] ?? 0 }}</span></div>
            </div>
            <div class="admin-info-list" style="margin-top:14px">
                <div class="admin-info-row"><span class="admin-label">Latest Result</span><span class="admin-value">{{ $latestScan->decision ?? 'Not recorded yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Last Scanned At</span><span class="admin-value">{{ $latestScan->timestamp ?? 'Not recorded yet' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Examiner</span><span class="admin-value">{{ $latestScan->examiner_name ?? 'Not recorded yet' }}</span></div>
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
