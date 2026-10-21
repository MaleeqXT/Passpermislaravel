<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Services\Monitor\Planning\MonitorPlanningPdfService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MonitorPlanningPdfExportTest extends TestCase
{
    private User $monitorUser;
    private Monitor $monitor;
    private Monitor $otherMonitor;
    private Student $student;
    private string $placeId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'planning_export_test');
        config()->set('database.connections.planning_export_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('planning_export_test');
        DB::setDefaultConnection('planning_export_test');
        Carbon::setTestNow('2026-10-16 10:00:00');

        foreach ($this->schemas() as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }

        DB::table('roles')->insert(['id' => 1, 'name' => 'monitor', 'guard_name' => 'web']);
        DB::table('roles')->insert(['id' => 2, 'name' => 'student', 'guard_name' => 'web']);
        DB::table('roles')->insert(['id' => 3, 'name' => 'admin', 'guard_name' => 'web']);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Moniteur Test', 'first_name' => 'Moniteur', 'last_name' => 'Test', 'email' => 'monitor@example.test', 'status' => 1],
            ['id' => 2, 'name' => 'Élève Réservé', 'first_name' => 'Élève', 'last_name' => 'Réservé', 'email' => 'student@example.test', 'status' => 1],
            ['id' => 3, 'name' => 'Autre Moniteur', 'first_name' => 'Autre', 'last_name' => 'Moniteur', 'email' => 'other-monitor@example.test', 'status' => 1],
            ['id' => 4, 'name' => 'Administrateur Test', 'first_name' => 'Administrateur', 'last_name' => 'Test', 'email' => 'admin@example.test', 'status' => 1],
        ]);
        DB::table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => User::class, 'model_id' => 1],
            ['role_id' => 2, 'model_type' => User::class, 'model_id' => 2],
            ['role_id' => 1, 'model_type' => User::class, 'model_id' => 3],
            ['role_id' => 3, 'model_type' => User::class, 'model_id' => 4],
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->monitor = Monitor::create(['user_id' => 1, 'status' => 1]);
        $this->otherMonitor = Monitor::create(['user_id' => 3, 'status' => 1]);
        $this->student = Student::create(['user_id' => 2]);
        $this->placeId = (string) Str::uuid();
        DB::table('zones')->insert(['id' => (string) Str::uuid(), 'name' => 'Creil']);
        $zoneId = DB::table('zones')->value('id');
        DB::table('lieux')->insert(['id' => $this->placeId, 'name' => 'Agence Creil', 'zone_id' => $zoneId, 'status' => 1]);

        $bookedReservation = Reservation::create([
            'monitor_id' => $this->monitor->id,
            'lieu_id' => $this->placeId,
            'date' => '2026-10-20',
            'start_at' => '09:00',
            'end_at' => '10:00',
            'hour' => 1,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        Training::create([
            'reservation_id' => $bookedReservation->id,
            'student_id' => $this->student->id,
            'offer_id' => null,
        ]);
        Reservation::create([
            'monitor_id' => $this->monitor->id,
            'lieu_id' => $this->placeId,
            'date' => '2026-10-21',
            'start_at' => '11:00',
            'end_at' => '12:00',
            'hour' => 1,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        Reservation::create([
            'monitor_id' => $this->otherMonitor->id,
            'lieu_id' => $this->placeId,
            'date' => '2026-10-20',
            'start_at' => '14:00',
            'end_at' => '15:00',
            'hour' => 1,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->monitorUser = User::findOrFail(1);
        $this->actingAs($this->monitorUser);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('planning_export_test');
        parent::tearDown();
    }

    public function test_monitor_can_download_its_complete_planning_and_cannot_select_another_monitor(): void
    {
        $response = $this->get('/api/monitor/reservations/export/pdf?monitor_id='.$this->otherMonitor->id);

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="planning-moniteur-test-complet.pdf"');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_administrator_can_export_the_monitor_selected_in_the_existing_preview(): void
    {
        $this->actingAs(User::findOrFail(4));

        $response = $this->get('/api/monitor/reservations/export/pdf?date_1=2026-10-20&date_2=2026-10-21&monitor_id='.$this->monitor->id);

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_complete_pdf_view_data_contains_the_booked_student_and_excludes_another_monitor(): void
    {
        $reservations = Reservation::query()
            ->where('monitor_id', $this->monitor->id)
            ->whereBetween('date', ['2026-10-20', '2026-10-21'])
            ->with(['training.student.user', 'training.cancellation', 'lieu.zone'])
            ->orderBy('date')
            ->get();
        $data = app(MonitorPlanningPdfService::class)->viewData(
            $this->monitor,
            $reservations,
        );

        $this->assertCount(2, $data['rows']);
        $this->assertSame('Planning complet', $data['periodLabel']);
        $this->assertSame('Élève Réservé', $data['rows'][0]['student']);
        $this->assertSame('Leçon réservée', $data['rows'][0]['status']);
        $this->assertSame('Disponibilité', $data['rows'][1]['status']);
    }

    public function test_monitor_can_download_an_empty_period_and_non_monitor_is_forbidden(): void
    {
        $this->get('/api/monitor/reservations/export/pdf?date_1=2026-11-01&date_2=2026-11-07')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->withoutMiddleware(HandleInertiaRequests::class);

        $this->actingAs(User::findOrFail(2));
        $this->get('/api/monitor/reservations/export/pdf?date_1=2026-10-20&date_2=2026-10-21')
            ->assertForbidden();
    }

    /** @return array<string, string> */
    private function schemas(): array
    {
        return [
            'users' => 'id INTEGER PRIMARY KEY, name TEXT, first_name TEXT, last_name TEXT, email TEXT, password TEXT, remember_token TEXT, email_verified_at TEXT, status INTEGER, deleted_at TEXT',
            'roles' => 'id INTEGER PRIMARY KEY, name TEXT, guard_name TEXT, created_at TEXT, updated_at TEXT',
            'model_has_roles' => 'role_id INTEGER, model_type TEXT, model_id INTEGER',
            'monitors' => 'id TEXT PRIMARY KEY, user_id INTEGER, status INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'students' => 'id TEXT PRIMARY KEY, user_id INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'secretaries' => 'id TEXT PRIMARY KEY, user_id INTEGER, deleted_at TEXT',
            'zones' => 'id TEXT PRIMARY KEY, name TEXT, deleted_at TEXT',
            'lieux' => 'id TEXT PRIMARY KEY, name TEXT, zone_id TEXT, status INTEGER, created_at TEXT, updated_at TEXT',
            'reservations' => 'id TEXT PRIMARY KEY, monitor_id TEXT, lieu_id TEXT, date TEXT, start_at TEXT, end_at TEXT, hour INTEGER, duration_minutes INTEGER, is_active INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'trainings' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, offer_id TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'cancellations' => 'id TEXT PRIMARY KEY, training_id TEXT, status INTEGER, deleted_at TEXT',
            'media' => 'id TEXT PRIMARY KEY, mediable_id TEXT, mediable_type TEXT, deleted_at TEXT',
            'competencies' => 'id TEXT PRIMARY KEY, status INTEGER, deleted_at TEXT',
        ];
    }
}
