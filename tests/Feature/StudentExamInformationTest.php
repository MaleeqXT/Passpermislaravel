<?php

namespace Tests\Feature;

use App\Enums\V2\Student\Examen\ExamenResultPermisEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentExamInformationTest extends TestCase
{
    private string $studentId;

    private string $otherStudentId;

    private User $studentUser;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'student_exam_information_test');
        config()->set('database.connections.student_exam_information_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('student_exam_information_test');
        DB::setDefaultConnection('student_exam_information_test');
        Carbon::setTestNow('2026-10-19 10:00:00');
        $this->withoutMiddleware(HandleInertiaRequests::class);

        foreach ($this->schemas() as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }

        $this->studentId = 'a1000000-0000-4000-8000-000000000001';
        $this->otherStudentId = 'a1000000-0000-4000-8000-000000000002';

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Élève Principal', 'first_name' => 'Élève', 'last_name' => 'Principal', 'email' => 'student@example.test', 'status' => 1],
            ['id' => 2, 'name' => 'Élève Autre', 'first_name' => 'Élève', 'last_name' => 'Autre', 'email' => 'other@example.test', 'status' => 1],
            ['id' => 3, 'name' => 'Administrateur', 'first_name' => 'Admin', 'last_name' => 'Principal', 'email' => 'admin@example.test', 'status' => 1],
        ]);
        DB::table('students')->insert([
            ['id' => $this->studentId, 'user_id' => 1, 'created_at' => '2026-01-01 00:00:00'],
            ['id' => $this->otherStudentId, 'user_id' => 2, 'created_at' => '2026-01-01 00:00:00'],
        ]);

        $this->studentUser = User::query()->findOrFail(1);
        $this->adminUser = User::query()->findOrFail(3);
        $this->adminUser->setRelation('roles', collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('student_exam_information_test');
        parent::tearDown();
    }

    public function test_student_with_no_exam_history_receives_an_empty_summary(): void
    {
        $this->actingAs($this->studentUser)
            ->getJson('/api/student/exams')
            ->assertOk()
            ->assertJsonPath('data.has_exam_record', false)
            ->assertJsonPath('data.next_exam', null)
            ->assertJsonPath('data.latest_exam', null)
            ->assertJsonPath('data.attempts', 0)
            ->assertJsonPath('data.completed_exams', 0);
    }

    public function test_student_with_a_scheduled_exam_receives_its_first_presentation(): void
    {
        $this->insertExam('exam-scheduled', $this->studentId, '2026-11-12', '09:30:00', ExamenStatusEnum::PENDING->value, null);

        $this->actingAs($this->studentUser)
            ->getJson('/api/student/exams')
            ->assertOk()
            ->assertJsonPath('data.has_exam_record', true)
            ->assertJsonPath('data.next_exam.date', '2026-11-12')
            ->assertJsonPath('data.next_exam.time', '09:30')
            ->assertJsonPath('data.next_exam.status', 'pending')
            ->assertJsonPath('data.attempts', 0)
            ->assertJsonPath('data.completed_exams', 0);
    }

    public function test_completed_exam_attempts_include_historical_reservations_without_duplicate_counting(): void
    {
        $this->insertExam('exam-duplicate', $this->studentId, '2026-10-10', '10:00:00', ExamenStatusEnum::FAILED->value, ExamenResultPermisEnum::REFUS->value);
        $this->insertHistoricalExamReservation('reservation-duplicate', 'training-duplicate', '2026-10-10');
        $this->insertExam('exam-passed', $this->studentId, '2026-10-15', '11:00:00', ExamenStatusEnum::SUCCESS->value, ExamenResultPermisEnum::ADMIS->value);

        $this->actingAs($this->studentUser)
            ->getJson('/api/student/exams')
            ->assertOk()
            ->assertJsonPath('data.attempts', 2)
            ->assertJsonPath('data.completed_exams', 2)
            ->assertJsonPath('data.successful_exams', 1)
            ->assertJsonPath('data.latest_exam.status', 'success')
            ->assertJsonPath('data.latest_exam.result', 'admitted');
    }

    public function test_completed_failed_exam_and_another_students_records_are_not_exposed(): void
    {
        $this->insertExam('exam-failed', $this->studentId, '2026-10-15', '11:00:00', ExamenStatusEnum::FAILED->value, ExamenResultPermisEnum::REFUS->value);
        $this->insertExam('exam-other', $this->otherStudentId, '2026-10-18', '13:00:00', ExamenStatusEnum::SUCCESS->value, ExamenResultPermisEnum::ADMIS->value);

        $this->actingAs($this->studentUser)
            ->getJson('/api/student/exams?student_id='.$this->otherStudentId)
            ->assertOk()
            ->assertJsonPath('data.attempts', 1)
            ->assertJsonPath('data.latest_exam.status', 'failed')
            ->assertJsonPath('data.latest_exam.result', 'refused');
    }

    public function test_admin_preview_uses_a_temporary_student_identity_and_can_be_revoked(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/students/'.$this->studentId.'/preview-session')
            ->assertOk()
            ->assertJsonPath('data.student_id', $this->studentId)
            ->assertJsonPath('data.user.id', $this->studentUser->id);

        $token = $response->json('data.token');
        $this->assertIsString($token);
        $this->assertNotNull(PersonalAccessToken::findToken($token));

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/student/exams')
            ->assertOk()
            ->assertJsonPath('data.attempts', 0);

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/student/preview-session')
            ->assertNoContent();

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_student_cannot_create_a_dashboard_preview_for_another_student(): void
    {
        $this->actingAs($this->studentUser)
            ->postJson('/api/admin/students/'.$this->otherStudentId.'/preview-session')
            ->assertForbidden();
    }

    private function insertExam(string $id, string $studentId, string $date, string $time, int $status, ?int $result): void
    {
        DB::table('student_exams')->insert([
            'id' => $id,
            'student_id' => $studentId,
            'date_examen' => $date,
            'heure_passage' => $time,
            'status' => $status,
            'result_permis' => $result,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }

    private function insertHistoricalExamReservation(string $reservationId, string $trainingId, string $date): void
    {
        DB::table('reservations')->insert([
            'id' => $reservationId,
            'date' => $date,
            'start_at' => '10:00:00',
            'end_at' => '11:00:00',
            'is_active' => 1,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        DB::table('trainings')->insert([
            'id' => $trainingId,
            'reservation_id' => $reservationId,
            'student_id' => $this->studentId,
            'session_type' => 'EXAMEN',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }

    /** @return array<string, string> */
    private function schemas(): array
    {
        return [
            'users' => 'id INTEGER PRIMARY KEY, name TEXT, first_name TEXT, last_name TEXT, email TEXT, password TEXT, remember_token TEXT, email_verified_at TEXT, status INTEGER, deleted_at TEXT',
            'roles' => 'id INTEGER PRIMARY KEY, name TEXT, guard_name TEXT, created_at TEXT, updated_at TEXT',
            'model_has_roles' => 'role_id INTEGER, model_type TEXT, model_id INTEGER',
            'students' => 'id TEXT PRIMARY KEY, user_id INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT',
            'monitors' => 'id TEXT PRIMARY KEY, user_id INTEGER, created_at TEXT, updated_at TEXT, deleted_at TEXT',
            'secretaries' => 'id TEXT PRIMARY KEY, user_id INTEGER, deleted_at TEXT',
            'student_exams' => 'id TEXT PRIMARY KEY, student_id TEXT, user_id INTEGER, monitor_id TEXT, lieu_id TEXT, is_auto INTEGER, comment TEXT, date_examen TEXT, heure_passage TEXT, result_permis INTEGER, status INTEGER, created_at TEXT, updated_at TEXT',
            'reservations' => 'id TEXT PRIMARY KEY, monitor_id TEXT, lieu_id TEXT, date TEXT, start_at TEXT, end_at TEXT, is_active INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'trainings' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, offer_id TEXT, session_type TEXT, prestation TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'cancellations' => 'id TEXT PRIMARY KEY, training_id TEXT, is_justified INTEGER, status INTEGER, comment TEXT, created_at TEXT, updated_at TEXT',
            'review_monitors' => 'id TEXT PRIMARY KEY, reservation_id TEXT, monitor_id TEXT, is_absent INTEGER, is_estimated INTEGER, estimation INTEGER, status INTEGER, created_at TEXT, updated_at TEXT',
            'lieux' => 'id TEXT PRIMARY KEY, zone_id TEXT, name TEXT, status INTEGER, created_at TEXT, updated_at TEXT',
            'zones' => 'id TEXT PRIMARY KEY, name TEXT, status INTEGER, created_at TEXT, updated_at TEXT',
            'media' => 'id TEXT PRIMARY KEY, mediable_id TEXT, mediable_type TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'personal_access_tokens' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, tokenable_type TEXT, tokenable_id INTEGER, name TEXT, token TEXT UNIQUE, abilities TEXT, last_used_at TEXT, expires_at TEXT, created_at TEXT, updated_at TEXT',
        ];
    }
}
