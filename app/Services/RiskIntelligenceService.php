<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RiskIntelligenceService
{
    private string $jsonPath;
    private string $htmlPath;

    public function __construct()
    {
        $this->jsonPath = storage_path('app/risk-analysis/risk_report.json');
        $this->htmlPath = storage_path('app/risk-analysis/risk_report.html');
    }

    public function viewModel(): array
    {
        $python = $this->loadPythonReport();

        if (($python['usable'] ?? false) === true) {
            return $python['model'];
        }

        $fallback = $this->liveLaravelSummary();

        if (($python['error'] ?? null) !== null) {
            $fallback['notice'] = 'Risk report exists but could not be parsed. Showing live Laravel summary instead.';
            $fallback['error'] = $python['error'];
        }

        return $fallback;
    }

    public function dashboardSummary(): array
    {
        $model = $this->viewModel();

        return [
            'source' => $model['source'],
            'source_label' => $model['source_label'],
            'status' => $model['status'],
            'notice' => $model['notice'],
            'generated_at' => $model['generated_at'],
            'total_scans' => (int) ($model['summary']['total_scans'] ?? 0),
            'duplicate_count' => (int) ($model['summary']['duplicate_count'] ?? 0),
            'high_risk_count' => (int) ($model['risk_overview']['high_risk_students_count'] ?? 0),
        ];
    }

    private function loadPythonReport(): array
    {
        if (! file_exists($this->jsonPath)) {
            return ['usable' => false, 'error' => null];
        }

        $decoded = json_decode((string) file_get_contents($this->jsonPath), true);

        if (! is_array($decoded)) {
            return ['usable' => false, 'error' => 'Risk report JSON could not be parsed.'];
        }

        return [
            'usable' => true,
            'error' => null,
            'model' => $this->normalizePythonReport($decoded),
        ];
    }

    private function normalizePythonReport(array $data): array
    {
        $summarySource = is_array($data['summary'] ?? null) ? $data['summary'] : $data;
        $overviewSource = is_array($data['risk_overview'] ?? null) ? $data['risk_overview'] : [];
        $students = collect($data['high_risk_students'] ?? [])->map(fn ($row) => $this->studentRow((array) $row))->values()->all();
        $examiners = collect($data['suspicious_examiners'] ?? [])->map(fn ($row) => $this->examinerRow((array) $row))->values()->all();
        $devices = collect($data['suspicious_devices'] ?? [])->map(fn ($row) => $this->deviceRow((array) $row, 'device'))->values()->all();
        $ips = collect($data['suspicious_ips'] ?? [])->map(fn ($row) => $this->deviceRow((array) $row, 'ip'))->values()->all();
        $highRiskStudents = collect($students)->where('risk_level', 'high')->count();

        return [
            'source' => 'python',
            'source_label' => 'Python report',
            'status' => $highRiskStudents > 0 ? 'Review needed' : 'Available',
            'notice' => 'Python-enhanced report loaded.',
            'error' => null,
            'generated_at' => $data['generated_at'] ?? $summarySource['generated_at'] ?? null,
            'json_path' => 'storage/app/risk-analysis/risk_report.json',
            'html_path' => 'storage/app/risk-analysis/risk_report.html',
            'html_exists' => file_exists($this->htmlPath),
            'summary' => $this->summaryShape($summarySource),
            'risk_overview' => [
                'high_risk_students_count' => (int) ($overviewSource['high_risk_students_count'] ?? $highRiskStudents),
                'suspicious_examiners_count' => (int) ($overviewSource['suspicious_examiners_count'] ?? count($examiners)),
                'suspicious_devices_count' => (int) ($overviewSource['suspicious_devices_count'] ?? count($devices)),
                'suspicious_ips_count' => (int) ($overviewSource['suspicious_ips_count'] ?? count($ips)),
                'duplicate_attempts' => (int) ($summarySource['duplicate_count'] ?? 0),
                'rejected_attempts' => (int) ($summarySource['rejected_count'] ?? 0),
            ],
            'risk_distribution' => $data['risk_distribution'] ?? $data['risk_summary'] ?? ['low' => 0, 'medium' => 0, 'high' => $highRiskStudents],
            'department_trends' => $this->trendRows($data['department_trends'] ?? []),
            'level_trends' => $this->trendRows($data['level_trends'] ?? []),
            'key_observations' => $this->nonEmptyList($data['key_observations'] ?? data_get($data, 'daily_summary.key_observations') ?? []),
            'high_risk_students' => $students,
            'suspicious_examiners' => $examiners,
            'suspicious_devices' => $devices,
            'suspicious_ips' => $ips,
            'recommendations' => $this->nonEmptyList($data['recommendations'] ?? data_get($data, 'daily_summary.recommendations') ?? []),
        ];
    }

    private function liveLaravelSummary(): array
    {
        $summary = $this->liveSummaryMetrics();
        $students = $this->fallbackStudentRisk();
        $examiners = $this->fallbackExaminerRisk();
        [$devices, $ips] = $this->fallbackDeviceIpRisk();
        $departmentTrends = $this->fallbackTrends('department');
        $levelTrends = $this->fallbackTrends('level');
        $observations = $this->fallbackObservations($summary, $students, $examiners, $devices, $ips);
        $recommendations = $this->fallbackRecommendations($summary, $students, $examiners, $devices, $ips);

        return [
            'source' => 'live',
            'source_label' => 'Live Laravel summary',
            'status' => 'Live summary available',
            'notice' => 'Python-enhanced report has not been generated yet. Showing live Laravel summary.',
            'error' => null,
            'generated_at' => now()->toIso8601String(),
            'json_path' => 'storage/app/risk-analysis/risk_report.json',
            'html_path' => 'storage/app/risk-analysis/risk_report.html',
            'html_exists' => file_exists($this->htmlPath),
            'summary' => $summary,
            'risk_overview' => [
                'high_risk_students_count' => collect($students)->where('risk_level', 'high')->count(),
                'suspicious_examiners_count' => count($examiners),
                'suspicious_devices_count' => count($devices),
                'suspicious_ips_count' => count($ips),
                'duplicate_attempts' => $summary['duplicate_count'],
                'rejected_attempts' => $summary['rejected_count'],
            ],
            'risk_distribution' => [
                'low' => collect($students)->where('risk_level', 'low')->count(),
                'medium' => collect($students)->where('risk_level', 'medium')->count(),
                'high' => collect($students)->where('risk_level', 'high')->count(),
            ],
            'department_trends' => $departmentTrends,
            'level_trends' => $levelTrends,
            'key_observations' => $observations,
            'high_risk_students' => $students,
            'suspicious_examiners' => $examiners,
            'suspicious_devices' => $devices,
            'suspicious_ips' => $ips,
            'recommendations' => $recommendations,
        ];
    }

    private function liveSummaryMetrics(): array
    {
        $scanCounts = $this->hasTable('verification_logs')
            ? DB::table('verification_logs')->select('decision', DB::raw('COUNT(*) as total'))->groupBy('decision')->pluck('total', 'decision')
            : collect();

        $total = (int) $scanCounts->sum();
        $approved = (int) ($scanCounts['APPROVED'] ?? 0);
        $rejected = (int) ($scanCounts['REJECTED'] ?? 0);
        $duplicate = (int) ($scanCounts['DUPLICATE'] ?? 0);
        $unusedTokens = $this->hasTable('qr_tokens') && Schema::hasColumn('qr_tokens', 'status')
            ? DB::table('qr_tokens')->whereIn('status', ['UNUSED', 'ACTIVE'])->count()
            : 0;

        return [
            'total_scans' => $total,
            'approved_count' => $approved,
            'rejected_count' => $rejected,
            'duplicate_count' => $duplicate,
            'approval_rate' => $this->rate($approved, $total),
            'duplicate_rate' => $this->rate($duplicate, $total),
            'rejection_rate' => $this->rate($rejected, $total),
            'total_students' => $this->countTable('students'),
            'verified_payments' => $this->countTable('payment_records'),
            'active_tokens' => $unusedTokens,
            'unused_tokens' => $unusedTokens,
        ];
    }

    private function fallbackStudentRisk(): array
    {
        if (! $this->hasTable('verification_logs') || ! $this->hasTable('qr_tokens')) {
            return [];
        }

        $query = DB::table('verification_logs')
            ->leftJoin('qr_tokens', 'verification_logs.token_id', '=', 'qr_tokens.token_id');
        $select = [
            'qr_tokens.student_id as matric_no',
            'verification_logs.decision',
            'qr_tokens.status as token_status',
            DB::raw('NULL as student_name'),
            DB::raw('NULL as level'),
            DB::raw('NULL as department'),
            DB::raw('NULL as verified_at'),
        ];

        if ($this->hasTable('students')) {
            $query->leftJoin('students', 'qr_tokens.student_id', '=', 'students.matric_no');
            $select[3] = 'students.full_name as student_name';
            $select[4] = 'students.level';

            if ($this->hasTable('departments')) {
                $query->leftJoin('departments', 'students.department_id', '=', 'departments.dept_id');
                $select[5] = 'departments.dept_name as department';
            }

            if ($this->hasTable('payment_records')) {
                $query->leftJoin('payment_records', 'students.matric_no', '=', 'payment_records.student_id');
                $select[6] = 'payment_records.verified_at';
            }
        }

        $rows = $query->select($select)
            ->orderByDesc('verification_logs.timestamp')
            ->limit(1000)
            ->get()
            ->filter(fn ($row) => ! empty($row->matric_no));

        return $rows->groupBy('matric_no')->map(function (Collection $logs, string $matric) {
            $first = $logs->first();
            $rejected = $logs->where('decision', 'REJECTED')->count();
            $duplicate = $logs->where('decision', 'DUPLICATE')->count();
            $tokenStatus = strtoupper((string) ($first->token_status ?? ''));
            $score = 0;
            $reasons = [];

            if ($duplicate >= 2) {
                $score += 35;
                $reasons[] = $duplicate . ' duplicate scan attempts';
            }
            if ($rejected >= 2) {
                $score += 30;
                $reasons[] = $rejected . ' rejected scan attempts';
            }
            if (empty($first->verified_at)) {
                $score += 20;
                $reasons[] = 'payment is missing or unverified';
            }
            if ($tokenStatus !== '' && ! in_array($tokenStatus, ['UNUSED', 'USED'], true)) {
                $score += 15;
                $reasons[] = 'token status is ' . $tokenStatus;
            }

            return $this->studentRow([
                'matric_no' => $matric,
                'student_name' => $first->student_name,
                'department' => $first->department,
                'level' => $first->level,
                'score' => $score,
                'risk_level' => $this->riskLevel($score),
                'reasons' => $reasons,
                'recommendation' => $score > 0 ? 'Review this student access activity before closing the session.' : 'No action required.',
            ]);
        })->filter(fn ($row) => ($row['score'] ?? 0) > 0)->sortByDesc('score')->take(15)->values()->all();
    }

    private function fallbackExaminerRisk(): array
    {
        if (! $this->hasTable('verification_logs')) {
            return [];
        }

        $query = DB::table('verification_logs');
        $select = ['verification_logs.examiner_id', 'verification_logs.decision', DB::raw('NULL as examiner_name')];

        if ($this->hasTable('examiners')) {
            $query->leftJoin('examiners', 'verification_logs.examiner_id', '=', 'examiners.examiner_id');
            $select[2] = 'examiners.full_name as examiner_name';
        }

        $rows = $query->select($select)->limit(1000)->get()
            ->filter(fn ($row) => ! empty($row->examiner_id));

        $average = max(1, (int) round($rows->count() / max(1, $rows->groupBy('examiner_id')->count())));

        return $rows->groupBy('examiner_id')->map(function (Collection $logs, string $examinerId) use ($average) {
            $first = $logs->first();
            $approved = $logs->where('decision', 'APPROVED')->count();
            $rejected = $logs->where('decision', 'REJECTED')->count();
            $duplicate = $logs->where('decision', 'DUPLICATE')->count();
            $score = 0;
            $reasons = [];

            if ($duplicate >= 3) {
                $score += 25;
                $reasons[] = 'high duplicate scan count';
            }
            if ($rejected >= 3) {
                $score += 30;
                $reasons[] = 'high rejected scan count';
            }
            if ($logs->count() >= 5 && $logs->count() > ($average * 1.5)) {
                $score += 15;
                $reasons[] = 'scan volume is high compared with peers';
            }

            return $this->examinerRow([
                'examiner_id' => $examinerId,
                'examiner_name' => $first->examiner_name,
                'total_scans' => $logs->count(),
                'approved_count' => $approved,
                'rejected_count' => $rejected,
                'duplicate_count' => $duplicate,
                'suspicious_score' => $score,
                'risk_level' => $this->riskLevel($score),
                'reasons' => $reasons,
                'recommendation' => $score > 0 ? 'Review examiner activity log and scanner assignment.' : 'No action required.',
            ]);
        })->filter(fn ($row) => ($row['suspicious_score'] ?? 0) > 0)->sortByDesc('suspicious_score')->take(15)->values()->all();
    }

    private function fallbackDeviceIpRisk(): array
    {
        if (! $this->hasTable('verification_logs')) {
            return [[], []];
        }

        $select = ['verification_logs.decision', 'verification_logs.examiner_id'];
        $hasDevice = Schema::hasColumn('verification_logs', 'device_fp');
        $hasIp = Schema::hasColumn('verification_logs', 'ip_address');

        if ($hasDevice) {
            $select[] = 'verification_logs.device_fp';
        }
        if ($hasIp) {
            $select[] = 'verification_logs.ip_address';
        }
        if ($this->hasTable('qr_tokens')) {
            $select[] = 'qr_tokens.student_id as matric_no';
            $query = DB::table('verification_logs')->leftJoin('qr_tokens', 'verification_logs.token_id', '=', 'qr_tokens.token_id');
        } else {
            $query = DB::table('verification_logs');
        }

        $rows = $query->select($select)->limit(1000)->get();

        return [
            $hasDevice ? $this->identifierRisk($rows, 'device_fp', 'device') : [],
            $hasIp ? $this->identifierRisk($rows, 'ip_address', 'ip') : [],
        ];
    }

    private function fallbackTrends(string $type): array
    {
        if (! $this->hasTable('verification_logs') || ! $this->hasTable('qr_tokens') || ! $this->hasTable('students')) {
            return [];
        }

        $query = DB::table('verification_logs')
            ->leftJoin('qr_tokens', 'verification_logs.token_id', '=', 'qr_tokens.token_id')
            ->leftJoin('students', 'qr_tokens.student_id', '=', 'students.matric_no');

        if ($type === 'department' && $this->hasTable('departments')) {
            $query->leftJoin('departments', 'students.department_id', '=', 'departments.dept_id');
            $label = 'departments.dept_name';
        } else {
            $label = 'students.level';
        }

        $rows = $query
            ->select(DB::raw("COALESCE($label, 'Unknown') as label"), 'verification_logs.decision')
            ->limit(1000)
            ->get()
            ->groupBy('label');

        return $rows->map(function (Collection $logs, string $label) {
            $total = $logs->count();
            $approved = $logs->where('decision', 'APPROVED')->count();
            $rejected = $logs->where('decision', 'REJECTED')->count();
            $duplicate = $logs->where('decision', 'DUPLICATE')->count();

            return [
                'label' => $label,
                'total_scans' => $total,
                'approved_count' => $approved,
                'rejected_count' => $rejected,
                'duplicate_count' => $duplicate,
                'approval_rate' => $this->rate($approved, $total),
                'duplicate_rate' => $this->rate($duplicate, $total),
                'rejection_rate' => $this->rate($rejected, $total),
                'risk_score' => ($duplicate * 20) + ($rejected * 15),
            ];
        })->sortByDesc('risk_score')->values()->all();
    }

    private function identifierRisk(Collection $rows, string $field, string $type): array
    {
        return $rows->filter(fn ($row) => ! empty($row->{$field}))
            ->groupBy($field)
            ->map(function (Collection $logs, string $identifier) use ($type) {
                $students = $logs->pluck('matric_no')->filter()->unique()->count();
                $examiners = $logs->pluck('examiner_id')->filter()->unique()->count();
                $rejected = $logs->where('decision', 'REJECTED')->count();
                $duplicate = $logs->where('decision', 'DUPLICATE')->count();
                $score = 0;
                $reasons = [];

                if ($students >= 4) {
                    $score += 25;
                    $reasons[] = 'identifier appears across many students';
                }
                if ($rejected >= 3) {
                    $score += 20;
                    $reasons[] = 'many rejected scans from this identifier';
                }
                if ($duplicate >= 3) {
                    $score += 20;
                    $reasons[] = 'many duplicate scans from this identifier';
                }
                if ($examiners >= 2) {
                    $score += 15;
                    $reasons[] = 'identifier linked to multiple examiners';
                }

                return $this->deviceRow([
                    'identifier' => $identifier,
                    'type' => $type,
                    'total_scans' => $logs->count(),
                    'unique_students' => $students,
                    'unique_examiners' => $examiners,
                    'rejected_count' => $rejected,
                    'duplicate_count' => $duplicate,
                    'risk_level' => $this->riskLevel($score),
                    'reasons' => $reasons,
                    'recommendation' => $score > 0 ? 'Review scanner/device context for repeated risk patterns.' : 'No action required.',
                ]);
            })->filter(fn ($row) => ! empty($row['reasons']))->sortByDesc('total_scans')->take(15)->values()->all();
    }

    private function fallbackObservations(array $summary, array $students, array $examiners, array $devices, array $ips): array
    {
        $items = ['Python-enhanced report has not been generated yet; showing live Laravel summary.'];

        if ($summary['total_scans'] === 0) {
            $items[] = 'No verification scan activity has been recorded yet.';
        }
        if ($summary['duplicate_count'] > 0) {
            $items[] = $summary['duplicate_count'] . ' duplicate scan attempt(s) detected.';
        }
        if ($summary['rejected_count'] > 0) {
            $items[] = $summary['rejected_count'] . ' rejected scan attempt(s) detected.';
        }
        $items[] = count($students) > 0 ? count($students) . ' student risk record(s) require review.' : 'No high-risk student activity detected from current records.';
        if (count($examiners) > 0) {
            $items[] = 'One or more examiners have elevated rejected or duplicate scan activity.';
        }
        if ((count($devices) + count($ips)) > 0) {
            $items[] = 'Device/IP patterns are available for review.';
        }

        return $items;
    }

    private function fallbackRecommendations(array $summary, array $students, array $examiners, array $devices, array $ips): array
    {
        $items = [
            'Generate the Python-enhanced report for deeper device and student risk scoring.',
            'Keep demo mode disabled for real production usage.',
        ];

        if ($summary['duplicate_count'] > 0) {
            $items[] = 'Review duplicate scan attempts before closing the exam session.';
        }
        if (count($examiners) > 0) {
            $items[] = 'Confirm suspicious examiner activity if rejected scans are unusually high.';
        }
        if (count($students) > 0) {
            $items[] = 'Review flagged student payment, QR token, and scan history.';
        }
        if ((count($devices) + count($ips)) > 0) {
            $items[] = 'Check whether repeated device/IP patterns match expected scanner assignments.';
        }

        return $items;
    }

    private function summaryShape(array $source): array
    {
        $total = (int) ($source['total_scans'] ?? 0);
        $approved = (int) ($source['approved_count'] ?? 0);
        $rejected = (int) ($source['rejected_count'] ?? 0);
        $duplicate = (int) ($source['duplicate_count'] ?? 0);

        return [
            'total_scans' => $total,
            'approved_count' => $approved,
            'rejected_count' => $rejected,
            'duplicate_count' => $duplicate,
            'approval_rate' => (float) ($source['approval_rate'] ?? $this->rate($approved, $total)),
            'duplicate_rate' => (float) ($source['duplicate_rate'] ?? $this->rate($duplicate, $total)),
            'rejection_rate' => (float) ($source['rejection_rate'] ?? $this->rate($rejected, $total)),
            'total_students' => (int) ($source['total_students'] ?? $this->countTable('students')),
            'verified_payments' => (int) ($source['verified_payments'] ?? $this->countTable('payment_records')),
            'active_tokens' => (int) ($source['active_tokens'] ?? 0),
            'unused_tokens' => (int) ($source['unused_tokens'] ?? 0),
        ];
    }

    private function studentRow(array $row): array
    {
        $score = (int) ($row['score'] ?? 0);

        return [
            'matric_no' => (string) ($row['matric_no'] ?? '-'),
            'student_name' => (string) ($row['student_name'] ?? '-'),
            'department' => (string) ($row['department'] ?? '-'),
            'level' => (string) ($row['level'] ?? '-'),
            'score' => $score,
            'risk_level' => strtolower((string) ($row['risk_level'] ?? $this->riskLevel($score))),
            'reasons' => $this->nonEmptyList($row['reasons'] ?? []),
            'recommendation' => (string) ($row['recommendation'] ?? 'Review this student activity.'),
        ];
    }

    private function examinerRow(array $row): array
    {
        $score = (int) ($row['suspicious_score'] ?? 0);

        return [
            'examiner_id' => (string) ($row['examiner_id'] ?? '-'),
            'examiner_name' => (string) ($row['examiner_name'] ?? '-'),
            'total_scans' => (int) ($row['total_scans'] ?? 0),
            'approved_count' => (int) ($row['approved_count'] ?? 0),
            'rejected_count' => (int) ($row['rejected_count'] ?? 0),
            'duplicate_count' => (int) ($row['duplicate_count'] ?? 0),
            'suspicious_score' => $score,
            'risk_level' => strtolower((string) ($row['risk_level'] ?? $this->riskLevel($score))),
            'reasons' => $this->nonEmptyList($row['reasons'] ?? []),
            'recommendation' => (string) ($row['recommendation'] ?? 'Review examiner activity log.'),
        ];
    }

    private function deviceRow(array $row, string $defaultType): array
    {
        return [
            'identifier' => (string) ($row['identifier'] ?? '-'),
            'type' => (string) ($row['type'] ?? $defaultType),
            'total_scans' => (int) ($row['total_scans'] ?? 0),
            'unique_students' => (int) ($row['unique_students'] ?? 0),
            'unique_examiners' => (int) ($row['unique_examiners'] ?? 0),
            'rejected_count' => (int) ($row['rejected_count'] ?? 0),
            'duplicate_count' => (int) ($row['duplicate_count'] ?? 0),
            'risk_level' => strtolower((string) ($row['risk_level'] ?? 'low')),
            'reasons' => $this->nonEmptyList($row['reasons'] ?? []),
            'recommendation' => (string) ($row['recommendation'] ?? 'Review scanner/device context.'),
        ];
    }

    private function trendRows(array $rows): array
    {
        return collect($rows)->map(fn ($row) => [
            'label' => (string) (($row['label'] ?? $row['name'] ?? 'Unknown')),
            'total_scans' => (int) ($row['total_scans'] ?? 0),
            'approved_count' => (int) ($row['approved_count'] ?? 0),
            'rejected_count' => (int) ($row['rejected_count'] ?? 0),
            'duplicate_count' => (int) ($row['duplicate_count'] ?? 0),
            'approval_rate' => (float) ($row['approval_rate'] ?? 0),
            'duplicate_rate' => (float) ($row['duplicate_rate'] ?? 0),
            'rejection_rate' => (float) ($row['rejection_rate'] ?? 0),
            'risk_score' => (int) ($row['risk_score'] ?? 0),
        ])->values()->all();
    }

    private function nonEmptyList(mixed $value): array
    {
        return collect((array) $value)->filter(fn ($item) => trim((string) $item) !== '')->values()->map(fn ($item) => (string) $item)->all();
    }

    private function riskLevel(int $score): string
    {
        return $score >= 61 ? 'high' : ($score >= 31 ? 'medium' : 'low');
    }

    private function rate(int $count, int $total): float
    {
        return $total > 0 ? round(($count / $total) * 100, 1) : 0.0;
    }

    private function countTable(string $table): int
    {
        return $this->hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
