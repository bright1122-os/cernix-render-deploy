@extends('layouts.admin-control')

@section('admin-title', 'Admin Payments')

@section('admin-content')
<div class="admin-page-head">
    <div><div class="cx-eyebrow">Remita Records</div><h1>Payments</h1><p>Dedicated payment control view with filters and drill-downs. No manual override is added here.</p></div>
</div>
<section class="admin-section">
    <div class="admin-section-head"><h2>Verified Payments</h2><span>{{ $payments->total() }} records</span></div>
    <div class="admin-section-body">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Search RRR, matric, name">
            <select name="department"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(request('department')===$department)>{{ $department }}</option>@endforeach</select>
            <input name="date_from" value="{{ request('date_from') }}" type="date">
            <input name="date_to" value="{{ request('date_to') }}" type="date">
            <button class="admin-action">Apply</button><a class="admin-action ghost" href="{{ route('admin.payments') }}">Reset</a>
        </form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>RRR</th><th>Student</th><th>Matric</th><th>Department</th><th>Amount</th><th>Status</th><th>Verified</th><th>QR</th><th>Action</th></tr></thead><tbody>
            @forelse($payments as $payment)
                <tr><td class="mono">{{ $payment->rrr_number }}</td><td class="safe">{{ $payment->full_name ?? 'Student unavailable' }}</td><td class="mono">{{ $payment->student_id }}</td><td>{{ $payment->dept_name ?? 'N/A' }} {{ $payment->level ? '- '.$payment->level : '' }}</td><td class="mono">{{ number_format((float) $payment->amount_confirmed, 2) }}</td><td><span class="admin-status green">Verified</span></td><td class="mono">{{ $payment->verified_at }}</td><td>{{ $payment->token_status ?? 'Missing' }}</td><td><a class="admin-action ghost" href="{{ route('admin.payments.show', $payment->rrr_number) }}">View</a></td></tr>
            @empty
                <tr><td colspan="9"><div class="admin-empty">No payment records found.</div></td></tr>
            @endforelse
        </tbody></table></div><div style="margin-top:14px">{{ $payments->links() }}</div>
    </div>
</section>
@endsection
