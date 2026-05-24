@extends('layouts.examiner-portal', ['title' => 'Audit Trail'])

@section('examiner-content')
<div class="ex-page-head">
    <div>
        <h1 class="ex-title">Audit Trail</h1>
        <p class="ex-subtitle">Traceability view for examiner actions. This page focuses on accountability, device context, and token references.</p>
    </div>
</div>

<section class="ex-panel ex-section-pad">
    @if(empty($auditRows))
        <p class="ex-empty">No audit activity is available for this examiner yet.</p>
    @else
        <div class="ex-list">
            @foreach($auditRows as $row)
                <article class="ex-record">
                    <div class="ex-record-top">
                        <div class="safe">
                            <strong>{{ $row['action'] }}</strong>
                            <div class="ex-muted">{{ $row['student'] }} · <span class="ex-mono">{{ $row['matric_no'] }}</span></div>
                        </div>
                        <span class="ex-muted">{{ $row['time'] }}</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:10px">
                        <div><span class="ex-muted">Token</span><div class="ex-mono safe">{{ $row['token_ref'] }}</div></div>
                        <div><span class="ex-muted">Device / IP</span><div class="safe">{{ $row['device_fp'] ?? 'Not available' }} · {{ $row['ip_address'] ?? 'Not available' }}</div></div>
                    </div>
                    <div style="margin-top:12px">
                        <a class="ex-action secondary" href="{{ $row['detail_url'] }}">View</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
