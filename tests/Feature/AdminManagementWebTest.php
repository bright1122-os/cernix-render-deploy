<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminManagementWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_create_active_examiner_that_persists_and_can_login(): void
    {
        $super = DB::table('examiners')->where('username', 'superadmin')->first();
        $username = 'qa_examiner_' . Str::lower(Str::random(6));

        $this->withSession($this->adminSession($super))
            ->post(route('admin.examiners.store'), [
                'full_name' => 'QA Persistence Examiner',
                'username' => $username,
                'password' => 'strongpass123',
                'role' => 'examiner',
            ])
            ->assertRedirect();

        $examiner = DB::table('examiners')->where('username', $username)->first();

        $this->assertNotNull($examiner);
        $this->assertSame('examiner', $examiner->role);
        $this->assertTrue((bool) $examiner->is_active);
        $this->assertTrue(Hash::check('strongpass123', $examiner->password_hash));

        $this->withSession($this->adminSession($super))
            ->get(route('admin.examiners'))
            ->assertOk()
            ->assertSee('QA Persistence Examiner')
            ->assertSee($username);

        $this->postJson('/examiner/login', [
            'username' => $username,
            'password' => 'strongpass123',
        ])->assertOk()
            ->assertJsonPath('redirect_url', '/examiner/dashboard');

        $this->withSession($this->adminSession($examiner))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_created_examiner_remains_visible_after_refresh(): void
    {
        $admin = DB::table('examiners')->where('username', 'admin1')->first();
        $username = 'admin_examiner_' . Str::lower(Str::random(6));

        $this->withSession($this->adminSession($admin))
            ->post(route('admin.examiners.store'), [
                'full_name' => 'Admin Created Examiner',
                'username' => $username,
                'password' => 'strongpass123',
                'role' => 'examiner',
            ])
            ->assertRedirect();

        $examiner = DB::table('examiners')->where('username', $username)->first();
        $this->assertNotNull($examiner);

        if (Schema::hasColumn('examiners', 'admin_user_id')) {
            $this->assertNull($examiner->admin_user_id);
        }

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.examiners'))
            ->assertOk()
            ->assertSee('Admin Created Examiner')
            ->assertSee('View');
    }

    public function test_student_list_and_view_show_clean_identity_without_qr_internals(): void
    {
        $admin = DB::table('examiners')->where('username', 'admin1')->first();
        $student = $this->createStudentRecord();

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.students'))
            ->assertOk()
            ->assertSee($student['full_name'])
            ->assertSee($student['matric_no'])
            ->assertSee('View');

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.students.show', ['student' => $student['matric_no']]))
            ->assertOk()
            ->assertSee('Identity and Access')
            ->assertSee('Payment')
            ->assertSee('Exam Access')
            ->assertSee('Scan Summary')
            ->assertSee($student['full_name'])
            ->assertSee($student['matric_no'])
            ->assertDontSee('encrypted_payload')
            ->assertDontSee('hmac_signature')
            ->assertDontSee('aes_key')
            ->assertDontSee('hmac_secret');
    }

    public function test_admin_intelligence_page_does_not_show_developer_paths_or_commands(): void
    {
        $admin = DB::table('examiners')->where('username', 'admin1')->first();

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.intelligence'))
            ->assertOk()
            ->assertSee('Risk Intelligence')
            ->assertSee('Total Scans')
            ->assertDontSee('JSON Path')
            ->assertDontSee('HTML Report Path')
            ->assertDontSee('storage/app/risk-analysis')
            ->assertDontSee('php artisan cernix')
            ->assertDontSee('python_services/risk_analyzer');
    }

    private function adminSession(object $account): array
    {
        return [
            'examiner_id' => (int) $account->examiner_id,
            'examiner_username' => $account->username,
            'examiner_name' => $account->full_name,
            'examiner_role' => $account->role,
        ];
    }

    private function createStudentRecord(): array
    {
        $department = DB::table('departments')->where('dept_name', 'Computer Science')->first();
        $session = DB::table('exam_sessions')->where('is_active', true)->first();
        $examiner = DB::table('examiners')->where('username', 'examiner1')->first();
        $matric = '220404014';
        $token = (string) Str::uuid();

        $student = [
            'matric_no' => $matric,
            'full_name' => 'Samuel Akinwale Bello',
            'department_id' => $department->dept_id,
            'session_id' => $session->session_id,
            'photo_path' => 'demo-passports/student-014.jpg',
            'created_at' => now(),
        ];

        foreach (['level' => '400', 'department_code' => '04', 'faculty_code' => '04'] as $column => $value) {
            if (Schema::hasColumn('students', $column)) {
                $student[$column] = $value;
            }
        }

        DB::table('students')->updateOrInsert(['matric_no' => $matric], $student);
        DB::table('payment_records')->updateOrInsert(
            ['rrr_number' => 'TEST-ADMIN-VIEW'],
            [
                'student_id' => $matric,
                'amount_declared' => 100000,
                'amount_confirmed' => 100000,
                'remita_response' => json_encode(['status' => 'Verified Demo Payment', 'source' => 'demo']),
                'verified_at' => now(),
            ]
        );
        DB::table('qr_tokens')->updateOrInsert(
            ['token_id' => $token],
            [
                'student_id' => $matric,
                'session_id' => $session->session_id,
                'encrypted_payload' => 'not-rendered-payload',
                'hmac_signature' => 'not-rendered-signature',
                'status' => 'UNUSED',
                'issued_at' => now(),
                'used_at' => null,
            ]
        );
        DB::table('verification_logs')->insert([
            'token_id' => $token,
            'examiner_id' => $examiner->examiner_id,
            'decision' => 'APPROVED',
            'timestamp' => now(),
            'device_fp' => 'test-device',
            'ip_address' => '127.0.0.1',
        ]);

        return $student;
    }
}
