@extends('layouts.student-portal')

@section('title', 'Student Exam Dashboard')

@section('student-content')
@php
    $registeredAt = $student->created_at ? \Illuminate\Support\Carbon::parse($student->created_at)->format('d M Y, H:i') : 'Not available';
    $paymentAt = $payment?->verified_at ? \Illuminate\Support\Carbon::parse($payment->verified_at)->format('d M Y, H:i') : null;
    $steps = [
        ['label' => 'Registration', 'value' => 'Complete', 'meta' => $registeredAt],
        ['label' => 'Payment', 'value' => $payment ? 'Verified' : 'Pending', 'meta' => $paymentAt ?: 'Awaiting payment record'],
        ['label' => 'QR Access', 'value' => $token->status ?? 'Pending', 'meta' => $token?->issued_at ? 'Issued ' . \Illuminate\Support\Carbon::parse($token->issued_at)->format('d M Y, H:i') : 'Token pending'],
        ['label' => 'Timetable', 'value' => $timetable->count() ? 'Assigned' : 'Not assigned', 'meta' => $timetable->count() ? $timetable->count() . ' exams available' : 'Check back after admin scheduling'],
    ];
@endphp

<div class="cx-page-head">
    <div class="cx-eyebrow">Student Portal</div>
    <h1>Student Exam Dashboard</h1>
    <p>A concise view of your exam readiness. Use the sidebar for full profile, payment, timetable, and Exam Access ID pages.</p>
</div>

<div class="cx-grid two cx-animate">
    <section class="cx-card cx-card-pad">
        <div class="student-mini">
            <x-student-photo :student="$student" size="passport" />
            <div style="min-width:0">
                <h2 style="margin:0;font-size:clamp(24px,5vw,36px);letter-spacing:-.05em">{{ $student->full_name }}</h2>
                <p class="cx-muted mono cx-safe" style="margin:6px 0 0">{{ $student->matric_no }}</p>
                <p class="cx-muted" style="margin:6px 0 0">{{ $student->dept_name ?? 'Department unavailable' }} · {{ $student->level ?? 'Level unavailable' }} Level</p>
                <p class="cx-muted" style="margin:6px 0 0">{{ $session->semester ?? 'Session' }} {{ $session->academic_year ?? '' }}</p>
            </div>
        </div>
    </section>

    <section class="cx-card cx-card-pad">
        <div class="cx-section-title"><h2>Quick Actions</h2><span>Next useful steps</span></div>
        <div class="cx-grid">
            <a class="btn btn-primary btn-block" href="{{ route('student.exam-access-id') }}">Open QR Generator / Exam Access ID</a>
            <a class="btn btn-ghost btn-block" href="{{ route('student.timetable') }}">View Timetable</a>
            <a class="btn btn-ghost btn-block" href="{{ route('student.exam-pass') }}">Print Pass</a>
        </div>
    </section>
</div>

<section class="cx-metric-grid" style="margin-top:16px">
    <div class="cx-metric"><span>Registered</span><b>Complete</b></div>
    <div class="cx-metric"><span>Payment</span><b>{{ $payment ? 'Verified' : 'Pending' }}</b></div>
    <div class="cx-metric"><span>QR</span><b>{{ $token->status ?? 'Pending' }}</b></div>
    <div class="cx-metric"><span>Timetable</span><b>{{ $timetable->count() ? $timetable->count() . ' Exams' : 'Not Assigned' }}</b></div>
    <div class="cx-metric"><span>Next Exam</span><b>{{ $nextExam->display_status ?? 'None' }}</b></div>
</section>

<div class="cx-grid two" style="margin-top:16px">
    <section class="cx-card cx-card-pad">
        <div class="cx-section-title"><h2>Next Exam</h2><span>{{ $nextExam ? $nextExam->display_status : 'No exam' }}</span></div>
        @if($nextExam)
            <h2 style="margin:0">{{ $nextExam->course_code }} · {{ $nextExam->course_title }}</h2>
            <p class="cx-muted" style="margin:8px 0 0">{{ \Illuminate\Support\Carbon::parse($nextExam->exam_date)->format('l, d M Y') }} · {{ substr($nextExam->start_time,0,5) }}{{ $nextExam->end_time ? ' - '.substr($nextExam->end_time,0,5) : '' }}</p>
            <p style="margin:10px 0 0"><span class="chip emerald">{{ $nextExam->display_status }}</span> <span class="cx-muted">{{ $nextExam->venue }}</span></p>
        @else
            <div class="cx-empty">No upcoming exam is assigned yet. Your timetable page will update when admin publishes entries for your department and level.</div>
        @endif
    </section>

    <section class="cx-card cx-card-pad">
        <div class="cx-section-title"><h2>Readiness Timeline</h2><span>Live status</span></div>
        <div class="cx-timeline">
            @foreach($steps as $index => $step)
                <article class="cx-step">
                    <div class="cx-step-dot">{{ $index + 1 }}</div>
                    <div>
                        <b>{{ $step['label'] }} · {{ $step['value'] }}</b>
                        <span>{{ $step['meta'] }}</span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>

<section class="cx-card cx-card-pad" style="margin-top:16px">
    <div class="cx-section-title"><h2>Access Activity</h2><span>{{ $scanHistory->count() }} recent scan records</span></div>
    @if($scanHistory->count())
        <div class="cx-table-wrap">
            <table class="cx-table">
                <thead><tr><th>Time</th><th>Decision</th><th>Examiner</th><th>Token</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($scanHistory as $scan)
                        <tr>
                            <td class="mono">{{ $scan->timestamp }}</td>
                            <td><span class="chip {{ $scan->decision === 'APPROVED' ? 'emerald' : ($scan->decision === 'DUPLICATE' ? 'amber' : 'red') }}">{{ $scan->decision }}</span></td>
                            <td>{{ $scan->examiner_name ?? $scan->examiner_username ?? 'Examiner unavailable' }}</td>
                            <td class="mono cx-safe">{{ Str::limit($scan->token_id, 18) }}</td>
                            <td><a class="btn btn-ghost" href="{{ route('student.scans.show', $scan->log_id) }}">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="cx-empty">No QR scan activity has been recorded for your access ID yet.</div>
    @endif
</section>

<div class="cx-grid two" style="margin-top:16px">
    <section class="cx-card cx-card-pad">
        <div class="cx-section-title"><h2>Payment Snapshot</h2><span>Proof lives on Payment</span></div>
        @if($payment)
            <p style="margin:0"><b>Verified</b> <span class="cx-muted">on {{ \Illuminate\Support\Carbon::parse($payment->verified_at)->format('d M Y, H:i') }}</span></p>
            <p class="cx-muted" style="margin:8px 0 0">₦{{ number_format($payment->amount_confirmed, 2) }} confirmed for {{ $session->semester ?? 'the active session' }}.</p>
            <p style="margin:12px 0 0"><a class="btn btn-ghost" href="{{ route('student.payment') }}">Open payment record</a></p>
        @else
            <div class="cx-empty">Payment record is not available. Re-register with a valid Remita RRR or contact support.</div>
        @endif
    </section>

    <section class="cx-card cx-card-pad">
        <div class="cx-section-title"><h2>Important Instructions</h2><span>Before exam day</span></div>
        <p class="cx-muted" style="margin:0;line-height:1.7">Arrive early, carry your institutional ID, present your CERNIX QR at the exam venue, and follow the hall shown on your timetable.</p>
        <p style="margin:12px 0 0"><a class="btn btn-ghost" href="{{ route('student.instructions') }}">Read all instructions</a></p>
    </section>
</div>

<section class="cx-card cx-card-pad" style="margin-top:16px">
    <div class="cx-section-title"><h2>Mini Timetable Preview</h2><span>{{ $timetable->count() }} entries</span></div>
    @include('student.partials.timetable-list', ['limit' => 3])
</section>
@endsection
