<?php

namespace Tests\Feature;

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\Cancellation;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Services\Monitor\Planning\MonitorPlanningCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MonitorPlanningCalendarExportTest extends TestCase
{
    private User $monitorUser;

    private Monitor $monitor;

    private Monitor $otherMonitor;

    private Reservation $bookedReservation;

    private Training $bookedTraining;

    private string $placeId;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'planning_calendar_test');
        config()->set('database.connections.planning_calendar_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('app.url', 'https://planning.example.test');
        DB::purge('planning_calendar_test');
        DB::setDefaultConnection('planning_calendar_test');
        Carbon::setTestNow('2026-10-16 10:00:00');

        foreach ($this->schemas() as $table => $columns) {
            DB::statement("CREATE TABLE {$table} ({$columns})");
        }

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'monitor', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'student', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'admin', 'guard_name' => 'web'],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Moniteur Test', 'first_name' => 'Moniteur', 'last_name' => 'Test', 'email' => 'monitor@example.test', 'status' => 1],
            ['id' => 2, 'name' => 'Élève Réservé', 'first_name' => 'Élève', 'last_name' => 'Réservé', 'email' => 'student@example.test', 'status' => 1],
            ['id' => 3, 'name' => 'Autre Moniteur', 'first_name' => 'Autre', 'last_name' => 'Moniteur', 'email' => 'other-monitor@example.test', 'status' => 1],
            ['id' => 4, 'name' => 'Élève Autre', 'first_name' => 'Élève', 'last_name' => 'Autre', 'email' => 'other-student@example.test', 'status' => 1],
            ['id' => 5, 'name' => 'Administrateur Test', 'first_name' => 'Administrateur', 'last_name' => 'Test', 'email' => 'admin@example.test', 'status' => 1],
        ]);
        DB::table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => User::class, 'model_id' => 1],
            ['role_id' => 2, 'model_type' => User::class, 'model_id' => 2],
            ['role_id' => 1, 'model_type' => User::class, 'model_id' => 3],
            ['role_id' => 2, 'model_type' => User::class, 'model_id' => 4],
            ['role_id' => 3, 'model_type' => User::class, 'model_id' => 5],
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->monitor = Monitor::create(['user_id' => 1, 'status' => 1]);
        $this->otherMonitor = Monitor::create(['user_id' => 3, 'status' => 1]);
        $student = Student::create(['user_id' => 2]);
        $otherStudent = Student::create(['user_id' => 4]);
        $this->placeId = (string) Str::uuid();
        $zoneId = (string) Str::uuid();
        DB::table('zones')->insert(['id' => $zoneId, 'name' => 'Creil']);
        DB::table('lieux')->insert(['id' => $this->placeId, 'name' => 'Agence Creil', 'zone_id' => $zoneId, 'status' => 1]);

        $this->bookedReservation = Reservation::create([
            'monitor_id' => $this->monitor->id,
            'lieu_id' => $this->placeId,
            'date' => '2026-10-20',
            'start_at' => '09:00',
            'end_at' => '10:00',
            'hour' => 1,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        $this->bookedTraining = Training::create([
            'reservation_id' => $this->bookedReservation->id,
            'student_id' => $student->id,
            'offer_id' => null,
        ]);

        $otherReservation = Reservation::create([
            'monitor_id' => $this->otherMonitor->id,
            'lieu_id' => $this->placeId,
            'date' => '2026-10-20',
            'start_at' => '14:00',
            'end_at' => '15:00',
            'hour' => 1,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        Training::create([
            'reservation_id' => $otherReservation->id,
            'student_id' => $otherStudent->id,
            'offer_id' => null,
        ]);

        $this->monitorUser = User::findOrFail(1);
        $this->actingAs($this->monitorUser);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('planning_calendar_test');
        parent::tearDown();
    }

    public function test_monitor_exports_the_selected_week_as_a_valid_private_icalendar(): void
    {
        $response = $this->get('/api/monitor/reservations/export/ics?date_1=2026-10-19&date_2=2026-10-25&monitor_id='.$this->otherMonitor->id);

        $response->assertOk()
            ->assertHeader('content-type', 'text/calendar; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="planning-moniteur-test-2026-10-19-au-2026-10-25.ics"');

        $calendar = $response->getContent();
        $this->assertStringContainsString("BEGIN:VCALENDAR\r\n", $calendar);
        $this->assertStringContainsString('TZID:Europe/Paris', $calendar);
        $this->assertStringContainsString('DTSTART;TZID=Europe/Paris:20261020T090000', $calendar);
        $this->assertStringContainsString('DTEND;TZID=Europe/Paris:20261020T100000', $calendar);
        $this->assertStringContainsString('Élève Réservé', $calendar);
        $this->assertStringContainsString('Agence Creil', $calendar);
        $this->assertStringContainsString('STATUS:CONFIRMED', $calendar);
        $this->assertStringContainsString('UID:reservation-'.strtolower($this->bookedReservation->id).'@planning.example.test', $calendar);
        $this->assertStringNotContainsString('Élève Autre', $calendar);
        $this->assertStringContainsString("END:VCALENDAR\r\n", $calendar);
    }

    public function test_administrator_preview_can_export_and_subscribe_to_the_explicit_monitor_only(): void
    {
        $this->actingAs(User::findOrFail(5));

        $export = $this->get('/api/monitor/reservations/export/ics?date_1=2026-10-19&date_2=2026-10-25&monitor_id='.$this->monitor->id);
        $export->assertOk();
        $this->assertStringContainsString('Élève Réservé', $export->getContent());
        $this->assertStringNotContainsString('Élève Autre', $export->getContent());

        $subscription = $this->getJson('/api/monitor/calendar/subscription?monitor_id='.$this->monitor->id)
            ->assertOk();
        $feedPath = parse_url($subscription->json('data.subscription_url'), PHP_URL_PATH);
        $feed = $this->get($feedPath)->assertOk();
        $this->assertStringContainsString('Élève Réservé', $feed->getContent());
        $this->assertStringNotContainsString('Élève Autre', $feed->getContent());
    }

    public function test_rescheduled_and_cancelled_lessons_keep_their_uid_and_emit_calendar_statuses(): void
    {
        $calendar = app(MonitorPlanningCalendarService::class)->output(
            $this->monitor,
            app(MonitorPlanningCalendarService::class)->reservations($this->monitor),
        );
        $uid = 'UID:reservation-'.strtolower($this->bookedReservation->id).'@planning.example.test';
        $this->assertStringContainsString($uid, $calendar);

        $this->bookedReservation->update(['date' => '2026-10-21', 'start_at' => '11:30', 'end_at' => '12:30']);
        $rescheduled = app(MonitorPlanningCalendarService::class)->output(
            $this->monitor,
            app(MonitorPlanningCalendarService::class)->reservations($this->monitor),
        );
        $this->assertStringContainsString($uid, $rescheduled);
        $this->assertStringContainsString('DTSTART;TZID=Europe/Paris:20261021T113000', $rescheduled);

        Cancellation::create([
            'training_id' => $this->bookedTraining->id,
            'status' => CancellationStatusEnum::SUCCESS_CANCELLED->value,
        ]);
        $cancelled = app(MonitorPlanningCalendarService::class)->output(
            $this->monitor,
            app(MonitorPlanningCalendarService::class)->reservations($this->monitor),
        );
        $this->assertStringContainsString($uid, $cancelled);
        $this->assertStringContainsString('STATUS:CANCELLED', $cancelled);
        $this->assertStringContainsString('SUMMARY:Leçon annulée', $cancelled);
    }

    public function test_subscription_token_is_private_regenerable_revocable_and_isolated_to_its_monitor(): void
    {
        $initial = $this->getJson('/api/monitor/calendar/subscription')->assertOk();
        $initialUrl = $initial->json('data.subscription_url');
        $initialToken = basename((string) parse_url($initialUrl, PHP_URL_PATH), '.ics');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $initialToken);
        $this->assertDatabaseHas('monitor_calendar_subscriptions', [
            'monitor_id' => $this->monitor->id,
            'token_hash' => hash('sha256', $initialToken),
        ]);
        $this->assertDatabaseMissing('monitor_calendar_subscriptions', ['encrypted_token' => $initialToken]);

        $feedPath = parse_url($initialUrl, PHP_URL_PATH);
        $feed = $this->get($feedPath)->assertOk();
        $this->assertStringContainsString('Élève Réservé', $feed->getContent());
        $this->assertStringNotContainsString('Élève Autre', $feed->getContent());

        $regenerated = $this->postJson('/api/monitor/calendar/subscription/regenerate')->assertOk();
        $newUrl = $regenerated->json('data.subscription_url');
        $this->assertNotSame($initialUrl, $newUrl);
        $this->get($feedPath)->assertNotFound();
        $this->get(parse_url($newUrl, PHP_URL_PATH))->assertOk();

        $this->deleteJson('/api/monitor/calendar/subscription')->assertNoContent();
        $this->get(parse_url($newUrl, PHP_URL_PATH))->assertNotFound();
    }

    public function test_empty_week_is_a_valid_calendar_and_a_student_cannot_manage_a_monitor_subscription(): void
    {
        $empty = $this->get('/api/monitor/reservations/export/ics?date_1=2026-11-01&date_2=2026-11-07');
        $empty->assertOk();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $empty->getContent());
        $this->assertStringNotContainsString('BEGIN:VEVENT', $empty->getContent());

        $this->withoutMiddleware(HandleInertiaRequests::class);
        $this->actingAs(User::findOrFail(2));
        $this->getJson('/api/monitor/calendar/subscription')->assertForbidden();
        $this->get('/api/monitor/reservations/export/ics?date_1=2026-10-19&date_2=2026-10-25')->assertForbidden();
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
            'cancellations' => 'id TEXT PRIMARY KEY, training_id TEXT, status INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'monitor_calendar_subscriptions' => 'id TEXT PRIMARY KEY, monitor_id TEXT UNIQUE, encrypted_token TEXT, token_hash TEXT UNIQUE, revoked_at TEXT, created_at TEXT, updated_at TEXT',
            'media' => 'id TEXT PRIMARY KEY, mediable_id TEXT, mediable_type TEXT, deleted_at TEXT',
            'competencies' => 'id TEXT PRIMARY KEY, status INTEGER, deleted_at TEXT',
        ];
    }
}
