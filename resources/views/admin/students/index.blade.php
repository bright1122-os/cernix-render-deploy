@extends('layouts.admin-control')

@section('admin-title', 'Admin Students')

@section('admin-content')
<div class="admin-page-head">
    <div>
        <div class="cx-eyebrow">Student Records</div>
        <h1>Students</h1>
        <p>Search and filter registered students. Full trace details live on each student record, not on the dashboard.</p>
    </div>
</div>

<section class="admin-section">
    <div class="admin-section-head">
        <h2>Registered Students</h2>
        <span>{{ $students->total() }} records</span>
    </div>
    <div class="admin-section-body">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Search name, matric, RRR">
            <select name="department">
                <option value="">All departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                @endforeach
            </select>
            <select name="level">
                <option value="">All levels</option>
                @foreach($levels as $level)
                    <option value="{{ $level }}" @selected(request('level') === $level)>{{ $level }}</option>
                @endforeach
            </select>
            <button class="admin-action" type="submit">Apply</button>
            <a class="admin-action ghost" href="{{ route('admin.students') }}">Reset</a>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Matric</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Payment</th>
                        <th>QR</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="safe"><strong>{{ $student->full_name }}</strong></td>
                            <td class="mono">{{ $student->matric_no }}</td>
                            <td>{{ $student->dept_name ?? 'Not available' }}</td>
                            <td>{{ $student->level ?? 'Not available' }}</td>
                            <td><span class="admin-status {{ $student->verified_at ? 'green' : 'amber' }}">{{ $student->verified_at ? 'Verified' : 'Pending' }}</span></td>
                            <td><span class="admin-status {{ ($student->token_status ?? '') === 'UNUSED' ? 'green' : 'amber' }}">{{ $student->token_status ?? 'Missing' }}</span></td>
                            <td class="mono">{{ $student->created_at ? \Carbon\Carbon::parse($student->created_at)->format('M d, Y') : 'Not available' }}</td>
                            <td><a class="admin-action ghost" href="{{ route('admin.students.show', ['student' => $student->matric_no]) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="admin-empty">No registered students match this filter.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px">{{ $students->links() }}</div>
    </div>
</section>
@endsection
