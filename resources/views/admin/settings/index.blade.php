@extends('layouts.admin-control')

@section('admin-title', 'Admin Settings')

@section('admin-content')
@php
    $roleLabel = \Illuminate\Support\Str::headline(strtolower((string) ($currentAdmin['role'] ?? 'admin')));
    $canManageSessions = $permissions['can_manage_sessions'] ?? false;
    $canManageFees = $permissions['can_manage_fees'] ?? false;
    $canManageSettings = $permissions['can_manage_settings'] ?? false;
@endphp

<div class="admin-page-head">
    <div>
        <div class="cx-eyebrow">Operational Controls</div>
        <h1>Settings</h1>
        <p>Super Admin controls live here. Admin users can inspect the current state without changing system-wide configuration.</p>
    </div>
    <span class="admin-status {{ $currentAdmin['is_super_admin'] ? 'green' : 'amber' }}">{{ $roleLabel }}</span>
</div>

@if(session('status'))
    <div class="admin-section" style="margin-bottom:16px"><div class="admin-section-body">{{ session('status') }}</div></div>
@endif
@if($errors->any())
    <div class="admin-section" style="margin-bottom:16px"><div class="admin-section-body" style="color:var(--red)">{{ $errors->first() }}</div></div>
@endif

<div class="metric-strip" style="margin-bottom:16px">
    <div class="metric-cell"><span class="metric-label">Active Session</span><b class="metric-value">{{ $activeSession ? 'Active' : 'Inactive' }}</b></div>
    <div class="metric-cell"><span class="metric-label">Demo Mode</span><b class="metric-value">{{ $demoStatus['enabled'] ? 'Enabled' : 'Disabled' }}</b></div>
    <div class="metric-cell"><span class="metric-label">Demo Passports</span><b class="metric-value">{{ $demoStatus['demo_passports'] }}/20</b></div>
    <div class="metric-cell"><span class="metric-label">Settings Store</span><b class="metric-value">{{ $settingsStorageReady ? 'Available' : 'Missing' }}</b></div>
</div>

<div class="admin-grid two">
    <section class="admin-section" id="active-session">
        <div class="admin-section-head">
            <h2>Active Session</h2>
            <span>{{ $canManageSessions ? 'Super Admin control enabled' : 'Read-only for Admin' }}</span>
        </div>
        <div class="admin-section-body">
            @if($activeSession)
                <div class="admin-info-list" style="margin-bottom:14px">
                    <div class="admin-info-row"><span class="admin-label">Semester</span><span class="admin-value">{{ $activeSession->semester }}</span></div>
                    <div class="admin-info-row"><span class="admin-label">Academic Year</span><span class="admin-value">{{ $activeSession->academic_year }}</span></div>
                    <div class="admin-info-row"><span class="admin-label">Status</span><span class="admin-value">Active</span></div>
                </div>
            @else
                <div class="admin-empty">No exam session is active.</div>
            @endif

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Session</th><th>Academic Year</th><th>Status</th><th>Control</th></tr></thead>
                    <tbody>
                        @foreach($sessions as $session)
                            <tr>
                                <td>{{ $session->semester }}</td>
                                <td>{{ $session->academic_year }}</td>
                                <td><span class="admin-status {{ $session->is_active ? 'green' : 'amber' }}">{{ $session->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>
                                    @if($canManageSessions)
                                        @if(! $session->is_active)
                                            <form method="POST" action="{{ route('admin.sessions.activate', $session->session_id) }}">@csrf @method('PATCH')<button class="admin-action" type="submit">Set Active</button></form>
                                        @else
                                            <form method="POST" action="{{ route('admin.sessions.close', $session->session_id) }}">@csrf @method('PATCH')<button class="admin-action ghost" type="submit">Close Session</button></form>
                                        @endif
                                    @else
                                        <span class="muted">Super Admin only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head">
            <h2>Demo Mode</h2>
            <span>{{ $canManageSettings ? 'Super Admin control enabled' : 'Read-only for Admin' }}</span>
        </div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Environment</span><span class="admin-value">{{ $demoStatus['app_env'] }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Effective Status</span><span class="admin-value">{{ $demoStatus['enabled'] ? 'Enabled' : 'Disabled' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Environment Override</span><span class="admin-value">{{ $demoStatus['environment_enabled'] ? 'Enabled by APP_ENV/config' : 'Not enabled by environment' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Stored Switch</span><span class="admin-value">{{ $demoStatus['stored_enabled'] ? 'Enabled' : 'Disabled' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Allowed Test RRR Pattern</span><span class="admin-value mono">TEST-*</span></div>
            </div>

            @if($canManageSettings)
                <form method="POST" action="{{ route('admin.settings.demo.update') }}" style="margin-top:16px">
                    @csrf @method('PATCH')
                    <label style="display:flex;gap:10px;align-items:center;font-weight:900">
                        <input type="checkbox" name="demo_mode_enabled" value="1" @checked($demoStatus['stored_enabled'])>
                        Enable stored demo mode
                    </label>
                    <p class="muted" style="margin:8px 0 12px">Local, testing, and staging environments remain demo-enabled even when this stored switch is off.</p>
                    <button class="admin-action" type="submit">Save Demo Mode</button>
                </form>
            @else
                <p class="muted" style="margin:14px 0 0">Only Super Admin can toggle stored demo mode.</p>
            @endif
        </div>
    </section>
</div>

<section class="admin-section" id="fee-mapping" style="margin-top:16px">
    <div class="admin-section-head">
        <h2>School Fee Mapping</h2>
        <span>{{ $canManageFees ? 'Editable by Super Admin' : 'Read-only for Admin' }}</span>
    </div>
    <div class="admin-section-body">
        @if($canManageFees)
            <form method="POST" action="{{ route('admin.settings.fees.update') }}">
                @csrf @method('PATCH')
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Faculty</th><th>Department</th><th>Required School Fee</th></tr></thead>
                        <tbody>
                            @foreach($departmentFees as $department => $fee)
                                <tr>
                                    <td>Faculty of Computing</td>
                                    <td>{{ $department }}</td>
                                    <td><input name="fees[{{ $department }}]" value="{{ number_format((float) $fee, 2, '.', '') }}" inputmode="decimal" required style="min-height:40px;border:1px solid var(--line-2);border-radius:12px;padding:0 12px"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button class="admin-action" type="submit" style="margin-top:14px">Save Fee Mapping</button>
            </form>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Faculty</th><th>Department</th><th>Required School Fee</th><th>Control</th></tr></thead>
                    <tbody>
                        @foreach($departmentFees as $department => $fee)
                            <tr>
                                <td>Faculty of Computing</td>
                                <td>{{ $department }}</td>
                                <td class="mono">₦{{ number_format($fee, 0) }}</td>
                                <td><span class="muted">Super Admin only</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<div class="admin-grid two" style="margin-top:16px">
    <section class="admin-section">
        <div class="admin-section-head"><h2>QR / Verification Rules</h2><span>Security rules are protected</span></div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                @foreach($verificationRules as $label => $status)
                    <div class="admin-info-row" style="grid-template-columns:1fr auto;align-items:center">
                        <span class="admin-value">{{ $label }}</span>
                        <span class="admin-status {{ $status === 'Disabled' ? 'red' : 'green' }}">{{ $status }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Scanner Settings / Status</h2><span>Operational guidance</span></div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                @foreach($scannerStatus as $label => $status)
                    <div class="admin-info-row"><span class="admin-label">{{ $label }}</span><span class="admin-value">{{ $status }}</span></div>
                @endforeach
            </div>
        </div>
    </section>
</div>

<div class="admin-grid two" style="margin-top:16px">
    <section class="admin-section" id="maintenance">
        <div class="admin-section-head"><h2>Role / Access Overview</h2><span>Current permissions</span></div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Signed In As</span><span class="admin-value">{{ $currentAdmin['name'] }} <span class="mono">({{ $currentAdmin['username'] }})</span></span></div>
                <div class="admin-info-row"><span class="admin-label">Role</span><span class="admin-value">{{ $roleLabel }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Allowed Areas</span><span class="admin-value">{{ implode(', ', $accessOverview['allowed'] ?? []) }}</span></div>
                @if(! empty($accessOverview['restricted']))
                    <div class="admin-info-row"><span class="admin-label">Restricted for Admin</span><span class="admin-value">{{ implode(', ', $accessOverview['restricted']) }}</span></div>
                @endif
                @if(! empty($accessOverview['super_admin']))
                    <div class="admin-info-row"><span class="admin-label">Super Admin Controls</span><span class="admin-value">{{ implode(', ', $accessOverview['super_admin']) }}</span></div>
                @endif
            </div>
        </div>
    </section>

    <section class="admin-section">
        <div class="admin-section-head"><h2>Maintenance / Cache Info</h2><span>{{ $permissions['can_manage_maintenance'] ? 'Super Admin status' : 'Read-only status' }}</span></div>
        <div class="admin-section-body">
            <div class="admin-info-list">
                <div class="admin-info-row"><span class="admin-label">Database</span><span class="admin-value">{{ $health['database'] ? 'Available' : 'Issue detected' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Storage</span><span class="admin-value">{{ $health['storage'] ? 'Writable' : 'Locked' }}</span></div>
                <div class="admin-info-row"><span class="admin-label">Cache Controls</span><span class="admin-value">{{ $permissions['can_manage_maintenance'] ? 'Safe status visible. Destructive cache actions stay in terminal.' : 'Super Admin only' }}</span></div>
            </div>
        </div>
    </section>
</div>
@endsection
