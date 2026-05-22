@php
    $status = strtoupper($token->status ?? 'UNAVAILABLE');
    $badge = match ($status) {
        'UNUSED' => 'ACTIVE',
        'USED' => 'USED',
        'REVOKED' => 'REVOKED',
        default => 'PENDING',
    };
    $badgeClass = match ($status) {
        'UNUSED' => 'is-active',
        'USED' => 'is-used',
        'REVOKED' => 'is-revoked',
        default => 'is-pending',
    };
    $tokenRef = $token?->token_id ? substr($token->token_id, 0, 8) . '...' . substr($token->token_id, -4) : 'Not available';
    $photoInitials = collect(explode(' ', $student->full_name ?? 'Student'))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('');
@endphp
<style>
    .exam-access-id-card { position: relative; overflow: hidden; width: min(520px, 100%); margin: 0 auto; background: rgba(255,255,255,.94); border: 1px solid var(--line); border-radius: 24px; box-shadow: var(--shadow-lg); }
    .exam-access-id-card::before { content: ""; position: absolute; inset: 0; background-image: url('/aaua-logo.png'); background-repeat: no-repeat; background-position: center; background-size: 116%; opacity: .24; z-index: 0; pointer-events: none; }
    .exam-access-id-card::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(255,255,255,.8), rgba(255,255,255,.58) 44%, rgba(255,255,255,.86)); z-index: 0; pointer-events: none; }
    .exam-access-id-card > * { position: relative; z-index: 1; }
    .id-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; background: rgba(255,255,255,.82); border-bottom: 1px solid var(--line); }
    .id-brand { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .id-brand img { width: 46px; height: 46px; object-fit: contain; flex: 0 0 auto; }
    .id-brand b { display: block; font-size: clamp(17px, 4vw, 22px); color: var(--navy); letter-spacing: -.04em; line-height: 1.05; }
    .id-brand span { display: block; margin-top: 3px; color: var(--ink-3); font-size: 11px; letter-spacing: .03em; }
    .id-badge { display: inline-flex; align-items: center; gap: 7px; padding: 7px 10px; border-radius: 999px; background: rgba(5,150,105,.11); border: 1px solid rgba(5,150,105,.28); color: var(--emerald); font-weight: 900; letter-spacing: .12em; font-size: 10px; flex: 0 0 auto; }
    .id-badge.is-used { background: rgba(180,83,9,.12); border-color: rgba(180,83,9,.25); color: var(--amber); }
    .id-badge.is-revoked { background: rgba(220,38,38,.12); border-color: rgba(220,38,38,.25); color: var(--red); }
    .id-badge.is-pending { background: rgba(107,112,133,.12); border-color: rgba(107,112,133,.25); color: var(--ink-3); }
    .id-badge::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .id-body { padding: 14px 16px 16px; display: grid; gap: 10px; }
    .id-primary { display: grid; gap: 10px; }
    .id-panel { background: rgba(255,255,255,.78); border: 1px solid var(--line); border-radius: 18px; padding: 12px; backdrop-filter: blur(3px); }
    .id-label { display: flex; align-items: center; gap: 10px; color: var(--ink-4); text-transform: uppercase; letter-spacing: .14em; font-size: 10px; font-weight: 900; margin-bottom: 10px; }
    .id-label::before, .id-label::after { content: ""; height: 1px; background: var(--line); flex: 1; }
    .identity-row { display: flex; gap: 16px; align-items: center; min-width: 0; }
    .id-photo { width: 78px; height: 98px; border-radius: 15px; object-fit: cover; border: 1px solid var(--line-2); background: var(--bg); box-shadow: var(--shadow-sm); flex: 0 0 auto; }
    .id-photo-fallback { width: 78px; height: 98px; border-radius: 15px; display: grid; place-items: center; border: 1px solid var(--line-2); background: var(--bg); color: var(--ink-3); font-weight: 900; flex: 0 0 auto; }
    .identity-row h2 { margin: 0; font-size: clamp(18px, 5vw, 25px); letter-spacing: -.045em; line-height: 1.05; overflow-wrap: anywhere; }
    .identity-row p { margin: 5px 0 0; color: var(--ink-3); line-height: 1.35; font-size: 12px; }
    .qr-panel { text-align: center; }
    .qr-box { width: min(210px, 100%); margin: 0 auto; padding: 9px; background: #fff; border: 1px solid var(--line-2); border-radius: 18px; box-shadow: var(--shadow); }
    .qr-box svg { width: 100%; height: auto; display: block; }
    .qr-note { margin-top: 8px; color: var(--ink-3); font-size: 11px; line-height: 1.4; }
    .id-grid { display: grid; border: 1px solid var(--line); border-radius: 20px; overflow: hidden; background: var(--line); gap: 1px; }
    .id-field { background: rgba(255,255,255,.82); padding: 9px 10px; min-width: 0; }
    .id-field span { display: block; color: var(--ink-4); font-size: 10px; text-transform: uppercase; letter-spacing: .13em; font-weight: 900; margin-bottom: 6px; }
    .id-field b { display: block; color: var(--ink); overflow-wrap: anywhere; line-height: 1.25; font-size: 12px; }
    .id-field .mono { font-size: 12px; }
    .id-foot { padding: 11px 16px; background: rgba(244,244,239,.9); border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; gap: 10px; color: var(--ink-3); font-weight: 800; font-size: 10px; letter-spacing: .04em; }
    .id-foot img { width: 26px; height: 26px; object-fit: contain; }
    @media (min-width: 760px) {
        .id-primary { grid-template-columns: minmax(0, 1fr) 230px; align-items: stretch; }
        .id-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .exam-access-id-card { border-radius: 22px; }
        .exam-access-id-card::before { background-size: 122%; opacity: .22; }
        .exam-access-id-card::after { background: linear-gradient(180deg, rgba(255,255,255,.82), rgba(255,255,255,.62) 46%, rgba(255,255,255,.88)); }
        .id-head { align-items: flex-start; }
        .id-brand img { width: 42px; height: 42px; }
        .id-badge { padding: 6px 9px; font-size: 9px; }
        .identity-row { align-items: flex-start; }
        .id-photo, .id-photo-fallback { width: 68px; height: 86px; border-radius: 14px; }
        .id-grid { grid-template-columns: 1fr; }
        .id-foot { flex-direction: column; align-items: flex-start; }
    }
    @media print {
        @page { size: A4; margin: 9mm; }
        .exam-access-id-card { box-shadow: none; break-inside: avoid; page-break-inside: avoid; width: 100%; border-radius: 18px; }
        .exam-access-id-card::before { opacity: .18 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .id-head { padding: 16px 20px; }
        .id-body { padding: 14px 20px 16px; gap: 10px; }
        .id-panel { padding: 13px; border-radius: 16px; }
        .id-primary { grid-template-columns: minmax(0, 1fr) 260px; gap: 10px; }
        .qr-box { width: 222px; padding: 8px; border-radius: 14px; }
        .id-photo, .id-photo-fallback { width: 74px; height: 94px; border-radius: 12px; }
        .identity-row h2 { font-size: 23px; }
        .id-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .id-field { padding: 8px 9px; }
        .id-table th, .id-table td { padding: 5px 6px; font-size: 10px; }
        .id-foot { padding: 10px 20px; font-size: 10px; }
    }
</style>

<article id="exam-access-id-card" class="exam-access-id-card">
    <header class="id-head">
        <div class="id-brand">
            <img src="/aaua-logo.png" alt="AAUA logo">
            <div>
                <b>Adekunle Ajasin University</b>
                <span>CERNIX · Secure Exam Verification</span>
            </div>
        </div>
        <div class="id-badge {{ $badgeClass }}">{{ $badge }}</div>
    </header>

    <div class="id-body">
        <section class="id-primary">
            <div class="id-panel">
                <div class="id-label">Student Identity</div>
                <div class="identity-row">
                    <x-student-photo :student="$student" size="passport" />
                    <div>
                        <h2>{{ $student->full_name }}</h2>
                        <p>{{ $student->matric_no }} · {{ $student->dept_name ?? 'Department unavailable' }}</p>
                        <p>{{ $student->faculty ?? 'Faculty unavailable' }} · {{ $student->level ?? 'Level unavailable' }} Level</p>
                    </div>
                </div>
            </div>

            <div class="id-panel qr-panel">
                <div class="id-label">Exam Access Token</div>
                <div class="qr-box">
                    @if($qrSvg)
                        {!! $qrSvg !!}
                    @else
                        <div style="padding:48px 12px;color:var(--ink-3)">QR not available</div>
                    @endif
                </div>
                <div class="qr-note">Present this slip at the examination venue. One-time server verification is required.</div>
            </div>
        </section>

        <section class="id-grid">
            <div class="id-field"><span>Session</span><b>{{ $session->semester ?? 'Not available' }} {{ $session->academic_year ?? '' }}</b></div>
            <div class="id-field"><span>Registration</span><b>{{ $student->created_at ? \Illuminate\Support\Carbon::parse($student->created_at)->format('d M Y, H:i') : 'Not available' }}</b></div>
            <div class="id-field"><span>Payment</span><b>{{ $payment ? 'Verified' : 'Not available' }}</b></div>
            <div class="id-field"><span>Remita RRR</span><b class="mono">{{ $payment->rrr_number ?? 'Not available' }}</b></div>
            <div class="id-field"><span>Amount Confirmed</span><b>{{ $payment ? '₦' . number_format($payment->amount_confirmed, 2) : 'Not available' }}</b></div>
            <div class="id-field"><span>Verified At</span><b>{{ $payment?->verified_at ? \Illuminate\Support\Carbon::parse($payment->verified_at)->format('d M Y, H:i') : 'Not available' }}</b></div>
            <div class="id-field"><span>QR Status</span><b>{{ $status }}</b></div>
            <div class="id-field"><span>Issued At</span><b>{{ $token?->issued_at ? \Illuminate\Support\Carbon::parse($token->issued_at)->format('d M Y, H:i') : 'Not available' }}</b></div>
            <div class="id-field"><span>Token Reference</span><b class="mono">{{ $tokenRef }}</b></div>
            <div class="id-field"><span>Next Exam</span><b>{{ $nextExam->course_code ?? 'Not assigned yet' }} {{ $nextExam->course_title ?? '' }}</b></div>
            <div class="id-field"><span>Date / Time</span><b>{{ $nextExam ? \Illuminate\Support\Carbon::parse($nextExam->exam_date)->format('d M Y') . ' · ' . substr($nextExam->start_time, 0, 5) : 'Not available' }}</b></div>
            <div class="id-field"><span>Venue</span><b>{{ $nextExam->venue ?? 'Not available' }}</b></div>
        </section>
    </div>

    <footer class="id-foot">
        <div>AES-256-GCM · HMAC-SHA256 · One-time QR verification</div>
        <div style="display:flex;align-items:center;gap:10px"><img src="/aaua-logo.png" alt=""> AAUA VERIFIED · {{ $generatedAt->format('d M Y, H:i') }}</div>
    </footer>
</article>
