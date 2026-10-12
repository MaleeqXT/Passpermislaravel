<?php

namespace Tests\Feature;

use App\Http\Controllers\V1\EndPoint\Monitor\FormationController;
use App\Models\DocumentEvaluation;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use App\Notifications\V1\Monitor\Training\StudentTrainingBookletNotification;
use App\Services\SatisfactionOfferTriggerService;
use App\Services\Student\Training\info\TrainingInterface;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MonitorFormationTest extends TestCase
{
    private User $actor;
    private Monitor $monitor;
    private Student $student;
    private string $offer;
    private string $place;
    private FormationController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'formation_test');
        config()->set('database.connections.formation_test', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge('formation_test');
        DB::setDefaultConnection('formation_test');
        Notification::fake();
        Carbon::setTestNow('2026-10-08 12:00:00');
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('DAY', fn ($date) => (int) substr($date, 8, 2));
        $pdo->sqliteCreateFunction('HOUR', fn ($time) => (int) substr($time, 0, 2));
        $schemas = [
            'users' => 'id INTEGER PRIMARY KEY, name TEXT, first_name TEXT, last_name TEXT, email TEXT, phone TEXT, media TEXT, date_naissance TEXT, adresse TEXT, postal TEXT, postal_code_1 TEXT, ville TEXT, status INTEGER, departement TEXT, deleted_at TEXT',
            'roles' => 'id INTEGER PRIMARY KEY, name TEXT, guard_name TEXT, created_at TEXT, updated_at TEXT',
            'model_has_roles' => 'role_id INTEGER, model_type TEXT, model_id INTEGER',
            'monitors' => 'id TEXT PRIMARY KEY, user_id INTEGER, status INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'monitor_information' => 'id TEXT PRIMARY KEY, monitor_id TEXT, departement TEXT, deleted_at TEXT',
            'students' => 'id TEXT PRIMARY KEY, user_id INTEGER, balance INTEGER, neph TEXT, neph_status TEXT, boite_type INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'secretaries' => 'id TEXT PRIMARY KEY, user_id INTEGER, deleted_at TEXT',
            'offers' => 'id TEXT PRIMARY KEY, name TEXT, is_auto INTEGER, balance INTEGER, type_offre TEXT, color TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'wallets' => 'id TEXT PRIMARY KEY, student_id TEXT, offer_id TEXT, balance INTEGER, balance_minutes INTEGER, status INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'lieux' => 'id TEXT PRIMARY KEY, name TEXT, zone_id TEXT, status INTEGER, created_at TEXT, updated_at TEXT',
            'lieu_monitor' => 'lieu_id TEXT, monitor_id TEXT',
            'zones' => 'id TEXT PRIMARY KEY, name TEXT, deleted_at TEXT',
            'reservations' => 'id TEXT PRIMARY KEY, monitor_id TEXT, lieu_id TEXT, date TEXT, start_at TEXT, end_at TEXT, hour INTEGER, duration_minutes INTEGER, is_active INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'trainings' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, offer_id TEXT, session_type TEXT, prestation TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'training_proposals' => 'id TEXT PRIMARY KEY, reservation_id TEXT, status INTEGER, deleted_at TEXT, created_at TEXT, updated_at TEXT',
            'review_monitors' => 'id TEXT PRIMARY KEY, reservation_id TEXT, monitor_id TEXT, is_absent INTEGER, comment TEXT, estimation REAL, created_at TEXT, updated_at TEXT',
            'document_evaluations' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, monitor_id TEXT, data TEXT, created_at TEXT, updated_at TEXT',
            'cancellations' => 'id TEXT PRIMARY KEY, training_id TEXT, status INTEGER, deleted_at TEXT',
            'media' => 'id TEXT PRIMARY KEY, mediable_id TEXT, mediable_type TEXT',
            'ratings' => 'id TEXT PRIMARY KEY, reservation_id TEXT, student_id TEXT, monitor_id TEXT, competency_id TEXT, rating INTEGER, comment TEXT, created_at TEXT, updated_at TEXT',
            'competencies' => 'id TEXT PRIMARY KEY, main_competency_id TEXT, label TEXT, status INTEGER, position INTEGER, created_at TEXT, updated_at TEXT',
            'competency_evaluation_details' => 'id TEXT PRIMARY KEY, rating_id TEXT, student_evaluation INTEGER, student_response TEXT, active_tab TEXT, created_at TEXT, updated_at TEXT',
        ];
        foreach ($schemas as $table => $columns) DB::statement("CREATE TABLE {$table} ({$columns})");
        DB::table('users')->insert(['id' => 1, 'name' => 'Test Monitor', 'first_name' => 'Test', 'last_name' => 'Monitor', 'email' => 'monitor@example.test', 'status' => 1]);
        DB::table('users')->insert(['id' => 2, 'name' => 'Test Student', 'email' => 'student@example.test', 'phone' => '0600000000', 'date_naissance' => '2000-01-15', 'adresse' => '12 rue des Tests', 'postal' => '06000', 'ville' => 'Nice', 'status' => 1]);
        $this->monitor = Monitor::create(['user_id' => 1, 'status' => 1]);
        DB::table('monitor_information')->insert(['id' => (string) Str::uuid(), 'monitor_id' => $this->monitor->id, 'departement' => '060']);
        $this->student = Student::create(['user_id' => 2, 'balance' => 10, 'neph' => '1234567890', 'neph_status' => 'avec_neph', 'boite_type' => 0]);
        $this->actor = User::findOrFail(1);
        $this->actingAs($this->actor);
        $this->offer = (string) Str::uuid();
        $this->place = (string) Str::uuid();
        DB::table('offers')->insert(['id' => $this->offer, 'name' => 'Test purchased offer', 'is_auto' => 0, 'balance' => 10]);
        DB::table('wallets')->insert(['id' => (string) Str::uuid(), 'student_id' => $this->student->id, 'offer_id' => $this->offer, 'balance' => 10, 'balance_minutes' => 600, 'status' => 1]);
        DB::table('lieux')->insert(['id' => $this->place, 'name' => 'Test existing location', 'status' => 1]);
        DB::table('lieu_monitor')->insert(['lieu_id' => $this->place, 'monitor_id' => $this->monitor->id]);
        $this->seedLesson('2026-10-07');
        $this->controller = app(FormationController::class);
        $this->mock(SatisfactionOfferTriggerService::class)->shouldReceive('processCompletedLesson')->zeroOrMoreTimes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('formation_test');
        parent::tearDown();
    }

    private function request(array $values = []): Request
    {
        $request = Request::create('/api/monitor/formation/'.$this->student->id, 'POST', $values);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $this->actor);
        return $request;
    }

    private function values(array $changes = []): array
    {
        return array_replace([
            'date' => '2026-10-10', 'start_at' => '09:00', 'duration_minutes' => 60,
            'monitor_id' => $this->monitor->id, 'offer_id' => $this->offer, 'lieu_id' => $this->place,
            'type' => 'Prestation', 'department' => '060', 'boite_auto' => false,
            'evaluation_initiale' => false, 'evaluation_permis' => true, 'is_absent' => false,
            'public_observation' => 'Public lesson note', 'teacher_observation' => 'Private instructor note',
            'maneuvers' => ['Stationnement'], 'maneuver_counts' => ['demarrage_cote' => 1, 'giratoire' => 2], 
        ], $changes);
    }

    private function seedLesson(string $date, string $studentId = '', string $monitorId = '', string $start = '09:00', int $minutes = 60): Reservation
    {
        $reservation = Reservation::create(['monitor_id' => $monitorId ?: $this->monitor->id, 'lieu_id' => $this->place, 'date' => $date, 'start_at' => $start, 'end_at' => Carbon::parse($start)->addMinutes($minutes)->format('H:i'), 'hour' => (int) ceil($minutes / 60), 'duration_minutes' => $minutes, 'is_active' => 1]);
        Training::create(['reservation_id' => $reservation->id, 'student_id' => $studentId ?: $this->student->id, 'offer_id' => $this->offer, 'session_type' => 'Prestation']);
        return $reservation;
    }

    private function expectStatus(int $status, callable $action): void
    {
        try { $action(); $this->fail('Expected an authorization/business-rule rejection.'); }
        catch (HttpException $exception) { $this->assertSame($status, $exception->getStatusCode()); }
    }

    public function test_create_update_delete_use_real_services_and_refund_reserved_hours(): void
    {
        $response = $this->controller->store($this->request($this->values(['duration_minutes' => 45])), $this->student, app(ReservationInterface::class), app(TrainingInterface::class));
        $this->assertSame(201, $response->getStatusCode());
        $lesson = $response->getData(true)['data'];
        $reservation = Reservation::findOrFail($lesson['id']);
        $this->assertSame(9, (int) DB::table('wallets')->value('balance'));
        $this->assertSame(555, (int) DB::table('wallets')->value('balance_minutes'));
        $this->assertSame('Private instructor note', $lesson['teacher_observation']);
        $this->assertSame('Public lesson note', $lesson['public_observation']);
        $this->assertSame(['Stationnement', 'Démarrage en côte', 'Giratoire × 2'], $lesson['maneuvers']);
        $this->assertSame(['demarrage_cote' => 1, 'giratoire' => 2], $lesson['maneuver_counts']);
        $this->assertTrue($lesson['evaluation_permis']);
        $this->controller->update($this->request($this->values(['duration_minutes' => 60, 'boite_auto' => true, 'evaluation_initiale' => true])), $this->student, $reservation, app(ReservationInterface::class));
        $this->assertSame(9, (int) DB::table('wallets')->value('balance'));
        $this->assertSame(540, (int) DB::table('wallets')->value('balance_minutes'));
        $this->assertTrue(DocumentEvaluation::where('reservation_id', $reservation->id)->first()->data['formation']['boite_auto']);
        $this->controller->destroy($this->request(), $this->student, $reservation->fresh(), app(ReservationInterface::class));
        $this->assertSame(10, (int) DB::table('wallets')->value('balance'));
        $this->assertSame(600, (int) DB::table('wallets')->value('balance_minutes'));
        $this->assertNull(Reservation::find($reservation->id));
        $this->assertNull(Training::where('reservation_id', $reservation->id)->first());
        $this->assertSame(10, (int) $this->student->fresh()->balance);
    }

    public function test_student_and_monitor_scope_prevent_cross_account_access(): void
    {
        $otherStudent = Student::create(['user_id' => 2, 'balance' => 0]);
        $otherMonitor = Monitor::create(['user_id' => 2, 'status' => 1]);
        $foreign = $this->seedLesson('2026-10-09', $otherStudent->id, $otherMonitor->id);
        $this->expectStatus(403, fn () => $this->controller->index($this->request(), $otherStudent));
        $this->expectStatus(403, fn () => $this->controller->update($this->request($this->values()), $this->student, $foreign, app(ReservationInterface::class)));
        $data = $this->controller->index($this->request(), $this->student)->getData(true)['data'];
        $this->assertCount(1, $data['lessons']);
        $this->assertSame(['060'], $data['options']['departments']);
        $this->assertSame($this->monitor->id, $data['options']['instructors'][0]['id']);
    }

    public function test_monitor_can_email_the_selected_students_training_booklet(): void
    {
        $this->seedLesson('2026-10-10', start: '10:15', minutes: 75);
        DB::table('users')->insert(['id' => 3, 'name' => 'Other Monitor', 'email' => 'other-monitor@example.test', 'status' => 1]);
        $otherMonitor = Monitor::create(['user_id' => 3, 'status' => 1]);
        $this->seedLesson('2026-10-11', $this->student->id, $otherMonitor->id, '11:45', 45);

        $response = $this->postJson('/api/monitor/formation/'.$this->student->id.'/livret');

        $response->assertOk()->assertJsonPath('data.lesson_count', 3);
        $this->get('/api/monitor/formation/'.$this->student->id.'/livret/download')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        Notification::assertSentOnDemandTimes(StudentTrainingBookletNotification::class, 1);
        Notification::assertSentOnDemand(StudentTrainingBookletNotification::class, function (StudentTrainingBookletNotification $notification, array $channels, $notifiable) {
            $mail = $notification->toMail($notifiable);

            $this->assertSame([
                'html' => 'emails.monitor-training-booklet',
                'text' => 'emails.monitor-training-booklet-text',
            ], $mail->view);
            $this->assertSame($this->student->user->name, $mail->viewData['studentName']);
            $this->assertSame('08/10/2026 à 12:00', $mail->viewData['generatedAt']);
            $this->assertArrayNotHasKey('lessons', $mail->viewData);
            $this->assertCount(1, $mail->rawAttachments);
            $this->assertCount(1, $mail->callbacks);

            $email = (new \Symfony\Component\Mime\Email())
                ->from('from@example.test')
                ->to('to@example.test')
                ->subject('Test')
                ->html('<img src="cid:passpermis-booklet-logo@passpermisfacile">');

            foreach ($mail->callbacks as $callback) {
                $callback($email);
            }

            $this->assertStringContainsString('Content-ID: <passpermis-booklet-logo@passpermisfacile>', $email->toString());

            return true;
        });
    }
    public function test_monitor_can_save_restore_and_securely_scope_lesson_schema(): void
    {
        $lesson = Reservation::firstOrFail();
        $schema = [
            'version' => 1,
            'canvas' => ['width' => 1000, 'height' => 620],
            'strokes' => [[
                'color' => '#169566', 'width' => 6,
                'points' => [['x' => 100, 'y' => 120], ['x' => 240, 'y' => 260]],
            ]],
            'objects' => [[
                'id' => 'schema-car-1', 'type' => 'car-yellow', 'x' => 500, 'y' => 300,
                'width' => 92, 'height' => 58, 'rotation' => 30,
            ], [
                'id' => 'schema-text-1', 'type' => 'text', 'x' => 320, 'y' => 420,
                'width' => 160, 'height' => 42, 'rotation' => 0, 'text' => 'Priorité à droite',
            ]],
            'background' => ['id' => 'roundabout', 'asset' => 'road-backgrounds/roundabout.jpg', 'name' => 'Giratoire', 'width' => 1000, 'height' => 620],
        ];

        $this->putJson('/api/monitor/formation/'.$this->student->id.'/'.$lesson->id.'/schema', ['schema' => $schema])
            ->assertOk()
            ->assertJsonPath('data.schema.objects.0.type', 'car-yellow')
            ->assertJsonPath('data.schema.objects.1.text', 'Priorité à droite')
            ->assertJsonPath('data.schema.background.id', 'roundabout');
        $this->getJson('/api/monitor/formation/'.$this->student->id.'/'.$lesson->id.'/schema')
            ->assertOk()
            ->assertJsonPath('data.schema.strokes.0.color', '#169566')
            ->assertJsonPath('data.editable', true);
        $stored = DocumentEvaluation::where('reservation_id', $lesson->id)->firstOrFail()->data;
        $this->assertSame($schema, $stored['formation']['schema']);

        $otherStudent = Student::create(['user_id' => 2, 'balance' => 0]);
        $otherMonitor = Monitor::create(['user_id' => 2, 'status' => 1]);
        $foreignLesson = $this->seedLesson('2026-10-12', $otherStudent->id, $otherMonitor->id);
        $this->putJson('/api/monitor/formation/'.$otherStudent->id.'/'.$foreignLesson->id.'/schema', ['schema' => $schema])
            ->assertForbidden();

        DocumentEvaluation::where('reservation_id', $lesson->id)->update(['data' => ['formation_signature' => ['signed_at' => now()->toIso8601String()]]]);
        $this->putJson('/api/monitor/formation/'.$this->student->id.'/'.$lesson->id.'/schema', ['schema' => $schema])
            ->assertOk();
        Reservation::whereKey($lesson->id)->update(['is_active' => false]);
        $this->putJson('/api/monitor/formation/'.$this->student->id.'/'.$lesson->id.'/schema', ['schema' => $schema])
            ->assertUnprocessable();
        $this->getJson('/api/monitor/formation/'.$this->student->id.'/'.$lesson->id.'/schema')
            ->assertOk()
            ->assertJsonPath('data.editable', false);
    }
    public function test_sign_requires_completed_attended_lesson_and_locks_saved_content(): void
    {
        $past = Reservation::first();
        $this->controller->update($this->request($this->values(['date' => '2026-10-07'])), $this->student, $past, app(ReservationInterface::class));
        $signature = ['accepted' => true, 'strokes' => [[['x' => 15, 'y' => 20], ['x' => 90, 'y' => 55]]]];
        $signed = $this->controller->sign($this->request($signature), $this->student, $past->fresh())->getData(true)['data'];
        $this->assertSame('signed', $signed['status']);
        $this->assertNotEmpty($signed['signature']['digest']);
        $this->assertFalse($signed['can_delete']);
        $this->expectStatus(423, fn () => $this->controller->update($this->request($this->values(['date' => '2026-10-07'])), $this->student, $past->fresh(), app(ReservationInterface::class)));
        $this->expectStatus(423, fn () => $this->controller->sign($this->request($signature), $this->student, $past->fresh()));
        $future = $this->seedLesson('2026-10-10');
        $this->expectStatus(422, fn () => $this->controller->sign($this->request($signature), $this->student, $future));
    }

    public function test_absence_and_cancelled_status_block_signing_and_cancelled_edits(): void
    {
        $past = Reservation::first();
        $this->controller->update($this->request($this->values(['date' => '2026-10-07', 'is_absent' => true])), $this->student, $past, app(ReservationInterface::class));
        $signature = ['accepted' => true, 'strokes' => [[['x' => 10, 'y' => 10], ['x' => 80, 'y' => 50]]]];
        $this->expectStatus(422, fn () => $this->controller->sign($this->request($signature), $this->student, $past->fresh()));
        $past->update(['is_active' => false]);
        $data = $this->controller->index($this->request(), $this->student)->getData(true)['data']['lessons'][0];
        $this->assertSame('cancelled', $data['status']);
        $this->expectStatus(422, fn () => $this->controller->update($this->request($this->values(['date' => '2026-10-07'])), $this->student, $past->fresh(), app(ReservationInterface::class)));
    }

    public function test_overlap_insufficient_balance_and_invalid_hours_do_not_write_lessons(): void
    {
        $future = $this->seedLesson('2026-10-10');
        $this->expectStatus(422, fn () => $this->controller->store($this->request($this->values()), $this->student, app(ReservationInterface::class), app(TrainingInterface::class)));
        DB::table('wallets')->update(['balance' => 0, 'balance_minutes' => 0]);
        $this->expectStatus(422, fn () => $this->controller->store($this->request($this->values(['date' => '2026-10-11'])), $this->student, app(ReservationInterface::class), app(TrainingInterface::class)));
        try {
            $this->controller->store($this->request($this->values(['duration_minutes' => 135])), $this->student, app(ReservationInterface::class), app(TrainingInterface::class));
            $this->fail('Invalid business hours accepted.');
        } catch (ValidationException $exception) { $this->assertArrayHasKey('duration_minutes', $exception->errors()); }
        $this->assertSame(2, Reservation::count());
        $this->assertSame(2, Training::count());
    }

    public function test_authenticated_http_routes_create_update_delete_and_reject_unrelated_students(): void
    {
        $base = '/api/monitor/formation/'.$this->student->id;
        $this->getJson($base)->assertOk()->assertJsonCount(1, 'data.lessons');
        $created = $this->postJson($base, $this->values())->assertCreated()->assertJsonPath('data.public_observation', 'Public lesson note');
        $lessonId = $created->json('data.id');
        $this->putJson($base.'/'.$lessonId, $this->values(['teacher_observation' => 'Updated private note']))
            ->assertOk()->assertJsonPath('data.teacher_observation', 'Updated private note');
        $this->deleteJson($base.'/'.$lessonId)->assertOk()->assertJsonPath('success', true);
        $otherStudent = Student::create(['user_id' => 2]);
        $this->getJson('/api/monitor/formation/'.$otherStudent->id)->assertForbidden();
        $this->assertSame(10, (int) DB::table('wallets')->value('balance'));
    }

    public function test_invalid_signatures_and_signed_competency_updates_are_rejected(): void
    {
        $past = Reservation::first();
        $signatureUrl = '/api/monitor/formation/'.$this->student->id.'/'.$past->id.'/sign';
        $this->postJson($signatureUrl, ['accepted' => true, 'strokes' => [[['x' => 10, 'y' => 10], ['x' => 11, 'y' => 10]]]])->assertUnprocessable();
        $signature = ['accepted' => true, 'strokes' => [[['x' => 10, 'y' => 10], ['x' => 80, 'y' => 50]]]];
        $this->postJson($signatureUrl, $signature)->assertOk()->assertJsonPath('data.status', 'signed');
        $skill = (string) Str::uuid();
        DB::table('competencies')->insert(['id' => $skill, 'label' => 'Existing database skill', 'status' => 1, 'position' => 1]);
        $this->putJson('/api/monitor/progress/'.$this->student->id.'/competencies/'.$skill, ['rating' => 3, 'reservation_id' => $past->id])->assertStatus(423);
        $this->assertSame(0, DB::table('ratings')->count());
        $this->deleteJson('/api/monitor/formation/'.$this->student->id.'/'.$past->id)->assertStatus(423);
    }

    public function test_competencies_are_saved_to_the_selected_lesson_through_existing_api(): void
    {
        DB::statement('CREATE TABLE main_competencies (id TEXT PRIMARY KEY, label TEXT, name TEXT, status INTEGER, position INTEGER)');
        $group = (string) Str::uuid(); $skill = (string) Str::uuid();
        DB::table('main_competencies')->insert(['id' => $group, 'label' => 'C1', 'name' => 'Database competency group', 'status' => 1, 'position' => 1]);
        DB::table('competencies')->insert(['id' => $skill, 'main_competency_id' => $group, 'label' => 'Existing database skill', 'status' => 1, 'position' => 1]);
        $past = Reservation::first();
        $this->putJson('/api/monitor/progress/'.$this->student->id.'/competencies/'.$skill, [
            'rating' => 4, 'observations' => 'Instructor skill observation', 'reservation_id' => $past->id,
            'student_evaluation' => true, 'student_response' => 'Retained student response', 'active_tab' => 'comment',
        ])->assertOk();
        $this->getJson('/api/monitor/formation/'.$this->student->id)->assertOk()
            ->assertJsonPath('data.lessons.0.competencies.0.rating', 4)
            ->assertJsonPath('data.lessons.0.competencies.0.studentResponse', 'Retained student response');
        $this->assertSame($past->id, DB::table('ratings')->value('reservation_id'));
    }

    public function test_public_observations_and_maneuvers_are_visible_to_the_student_without_private_teacher_notes(): void
    {
        DB::statement('CREATE TABLE reservation_comments (id INTEGER PRIMARY KEY, reservation_id TEXT, student_id TEXT, comment TEXT, created_at TEXT, updated_at TEXT)');
        $past = Reservation::first();
        $this->controller->update($this->request($this->values(['date' => '2026-10-07'])), $this->student, $past, app(ReservationInterface::class));
        $this->actingAs(User::findOrFail(2));
        $response = $this->getJson('/api/student/progress/lessons/'.$past->id)->assertOk()
            ->assertJsonPath('data.publicObservation', 'Public lesson note')
            ->assertJsonPath('data.maneuvers.0', 'Stationnement')
            ->assertJsonPath('data.evaluations.permit', true);
        $this->assertStringNotContainsString('Private instructor note', $response->getContent());
    }

    public function test_progress_pages_receive_maneuver_counts_saved_from_formation_lessons(): void
    {
        DB::statement('CREATE TABLE main_competencies (id TEXT PRIMARY KEY, label TEXT, name TEXT, status INTEGER, position INTEGER)');
        $firstLesson = Reservation::firstOrFail();
        $secondLesson = $this->seedLesson('2026-10-09');
        DB::table('users')->insert(['id' => 3, 'name' => 'Other Monitor', 'email' => 'other-monitor@example.test', 'status' => 1]);
        $otherMonitor = Monitor::create(['user_id' => 3, 'status' => 1]);
        $otherMonitorLesson = $this->seedLesson('2026-10-11', monitorId: $otherMonitor->id);

        DocumentEvaluation::create([
            'reservation_id' => $firstLesson->id,
            'student_id' => $this->student->id,
            'monitor_id' => $this->monitor->id,
            'data' => ['formation' => ['maneuver_counts' => ['giratoire' => 2, 'creneau_droit' => 1]]],
        ]);
        DocumentEvaluation::create([
            'reservation_id' => $secondLesson->id,
            'student_id' => $this->student->id,
            'monitor_id' => $this->monitor->id,
            'data' => ['formation' => ['maneuver_counts' => ['giratoire' => 1, 'demi_tour' => 2]]],
        ]);
        DocumentEvaluation::create([
            'reservation_id' => $otherMonitorLesson->id,
            'student_id' => $this->student->id,
            'monitor_id' => $otherMonitor->id,
            'data' => ['formation' => ['maneuver_counts' => ['epi_gauche' => 1]]],
        ]);

        $this->getJson('/api/monitor/progress/'.$this->student->id)->assertOk()
            ->assertJsonPath('data.maneuverCounts.giratoire', 3)
            ->assertJsonPath('data.maneuverCounts.creneau_droit', 1)
            ->assertJsonPath('data.maneuverCounts.demi_tour', 2)
            ->assertJsonPath('data.maneuverCounts.epi_gauche', 1);

        $this->actingAs(User::findOrFail(2));
        $this->getJson('/api/student/progress')->assertOk()
            ->assertJsonPath('data.maneuverCounts.giratoire', 3)
            ->assertJsonPath('data.maneuverCounts.creneau_droit', 1)
            ->assertJsonPath('data.maneuverCounts.demi_tour', 2)
            ->assertJsonPath('data.maneuverCounts.epi_gauche', 1);
    }
    public function test_student_formation_hides_private_notes_and_can_save_its_own_schema(): void
    {
        $lesson = Reservation::firstOrFail();
        DocumentEvaluation::create([
            'reservation_id' => $lesson->id,
            'student_id' => $this->student->id,
            'monitor_id' => $this->monitor->id,
            'data' => [
                'formation' => [
                    'teacher_observation' => 'Private monitor note',
                    'maneuver_counts' => ['epi_gauche' => 1],
                    'schema' => ['version' => 1, 'canvas' => ['width' => 1000, 'height' => 620], 'strokes' => [], 'objects' => [], 'background' => null],
                ],
                'public_observations' => 'Public lesson note',
            ],
        ]);

        $this->actingAs(User::findOrFail(2));
        $response = $this->getJson('/api/student/formation')->assertOk()
            ->assertJsonPath('data.student.id', $this->student->id)
            ->assertJsonPath('data.lessons.0.public_observation', 'Public lesson note')
            ->assertJsonPath('data.lessons.0.maneuver_counts.epi_gauche', 1)
            ->assertJsonMissing(['teacher_observation' => 'Private monitor note']);
        $this->assertStringNotContainsString('Private monitor note', $response->getContent());
        $this->getJson('/api/student/formation/lessons/'.$lesson->id.'/schema')->assertOk()
            ->assertJsonPath('data.editable', true)
            ->assertJsonPath('data.schema.version', 1);

        $schema = ['version' => 1, 'canvas' => ['width' => 1000, 'height' => 620], 'strokes' => [], 'objects' => [], 'background' => null];
        $this->putJson('/api/student/formation/lessons/'.$lesson->id.'/schema', ['schema' => $schema])->assertOk()
            ->assertJsonPath('data.editable', true)
            ->assertJsonPath('data.schema.canvas.width', 1000);
        $this->assertSame($schema, DocumentEvaluation::where('reservation_id', $lesson->id)->firstOrFail()->data['formation']['schema']);

        $this->postJson('/api/student/formation/booklet', ['email' => 'other@example.test'])->assertOk();
        Notification::assertSentOnDemand(StudentTrainingBookletNotification::class, function ($notification, $channels, $notifiable): bool {
            return ($notifiable->routes['mail'] ?? null) === 'student@example.test';
        });
    }
    public function test_history_is_newest_first_with_independent_numbers_and_saved_evaluation_details(): void
    {
        $recent = $this->seedLesson('2026-10-09');
        $competency = (string) Str::uuid(); $rating = (string) Str::uuid();
        DB::table('competencies')->insert(['id' => $competency, 'label' => 'Database skill']);
        DB::table('ratings')->insert(['id' => $rating, 'reservation_id' => $recent->id, 'competency_id' => $competency, 'rating' => 3, 'student_id' => $this->student->id, 'monitor_id' => $this->monitor->id]);
        DB::table('competency_evaluation_details')->insert(['id' => (string) Str::uuid(), 'rating_id' => $rating, 'student_evaluation' => 1, 'student_response' => 'Existing student response', 'active_tab' => 'comment']);
        $lessons = $this->controller->index($this->request(), $this->student)->getData(true)['data']['lessons'];
        $this->assertSame($recent->id, $lessons[0]['id']);
        $this->assertSame(2, $lessons[0]['number']);
        $this->assertSame('upcoming', $lessons[0]['status']);
        $this->assertTrue($lessons[0]['competencies'][0]['studentEvaluation']);
        $this->assertSame('Existing student response', $lessons[0]['competencies'][0]['studentResponse']);
        $this->assertSame(1, $lessons[1]['number']);
    }

    public function test_exact_minute_durations_eval_rule_overlap_and_maneuver_counts_are_enforced(): void
    {
        $created = $this->controller->store(
            $this->request($this->values(['duration_minutes' => 45, 'maneuver_counts' => ['giratoire' => 2, 'epi_droit' => 1]])),
            $this->student,
            app(ReservationInterface::class),
            app(TrainingInterface::class),
        )->getData(true)['data'];

        $this->assertSame(45, $created['duration_minutes']);
        $this->assertSame('09:45', Reservation::findOrFail($created['id'])->end_at->format('H:i'));
        $this->assertSame(['giratoire' => 2, 'epi_droit' => 1], $created['maneuver_counts']);
        $this->assertSame(555, (int) DB::table('wallets')->value('balance_minutes'));
        $this->expectStatus(422, fn () => $this->controller->store(
            $this->request($this->values(['duration_minutes' => 60, 'start_at' => '09:30'])),
            $this->student,
            app(ReservationInterface::class),
            app(TrainingInterface::class),
        ));

        try {
            $this->controller->store(
                $this->request($this->values(['date' => '2026-10-11', 'duration_minutes' => 45, 'evaluation_initiale' => true])),
                $this->student,
                app(ReservationInterface::class),
                app(TrainingInterface::class),
            );
            $this->fail('Evaluation initiale was accepted for a non-60-minute duration.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('evaluation_initiale', $exception->errors());
        }
    }
    public function test_initial_evaluation_fiche_reuses_the_existing_document_evaluation_record(): void
    {
        $base = '/api/monitor/formation/'.$this->student->id.'/initial-evaluation';
        $initial = $this->getJson($base)->assertOk()
            ->assertJsonPath('data.student.name', 'Test Student')
            ->assertJsonPath('data.student.email', 'student@example.test')
            ->assertJsonPath('data.student.address', '12 rue des Tests')
            ->assertJsonPath('data.student.city', 'Nice')
            ->assertJsonPath('data.student.neph', '1234567890')
            ->assertJsonPath('data.formation.offer_name', 'Test purchased offer')
            ->assertJsonPath('data.formation.location_name', 'Test existing location')
            ->assertJsonPath('data.formation.monitor_name', 'Test Monitor')
            ->assertJsonPath('data.formation.duration_minutes', 60)
            ->assertJsonPath('data.formation.booked_lessons', 1)
            ->assertJsonPath('data.formation.booked_hours', 1)
            ->assertJsonPath('data.evaluation.reservation_id', Reservation::firstOrFail()->id)
            ->assertJsonPath('data.evaluation.data.training.manual', true);

        $documentCount = DocumentEvaluation::count();
        $data = $initial->json('data.evaluation.data');
        $data['student']['profession'] = 'Conducteur professionnel';
        $data['student']['wears_correction'] = true;
        $data['experience']['license']['am'] = true;
        $data['vehicle_knowledge']['steering'] = true;
        $data['attitude']['desire_to_do_it'] = true;
        $data['skills']['vehicle_setup'] = 7;
        $data['understanding']['comprehension'] = 8;
        $data['environment']['orientation'] = 6;
        $data['emotions']['relationship'] = 9;
        $data['results']['lessons_proposed'] = 30;
        $data['results']['proposal_accepted'] = 'Oui';

        $balanceBefore = (int) DB::table('wallets')->value('balance_minutes');
        $this->putJson($base, ['data' => $data])->assertOk()
            ->assertJsonPath('data.evaluation.data.student.profession', 'Conducteur professionnel')
            ->assertJsonPath('data.evaluation.data.results.lessons_proposed', 30)
            ->assertJsonPath('data.evaluation.data.results.proposal_accepted', 'Oui');

        $this->assertSame($documentCount + 1, DocumentEvaluation::count());
        $this->assertSame($balanceBefore, (int) DB::table('wallets')->value('balance_minutes'));
        $this->getJson($base)->assertOk()
            ->assertJsonPath('data.evaluation.data.skills.vehicle_setup', 7)
            ->assertJsonPath('data.evaluation.data.environment.orientation', 6);

        $otherStudent = Student::create(['user_id' => 2]);
        $this->getJson('/api/monitor/formation/'.$otherStudent->id.'/initial-evaluation')->assertForbidden();

        $evaluation = DocumentEvaluation::firstOrFail();
        $stored = $evaluation->data;
        $stored['formation_signature'] = ['signed_at' => now()->toIso8601String()];
        $evaluation->update(['data' => $stored]);
        $this->putJson($base, ['data' => $data])->assertStatus(423);
    }
    public function test_initial_evaluation_questions_persist_in_the_existing_evaluation_and_sync_supported_fiche_values(): void
    {
        $base = '/api/monitor/formation/'.$this->student->id.'/initial-evaluation/questions';
        $initial = $this->getJson($base)->assertOk()
            ->assertJsonPath('data.student.name', 'Test Student')
            ->assertJsonPath('data.evaluation.current_question', 1)
            ->assertJsonPath('data.evaluation.status', 'in_progress');

        $documentCount = DocumentEvaluation::count();
        $balanceBefore = (int) DB::table('wallets')->value('balance_minutes');
        $answers = [
            'q01' => ['Boîte mécanique', 'Conduite accompagnée'],
            'q03' => 'Devenir conducteur professionnel',
            'q05' => 'Oui',
            'q08' => 'Oui',
            'q10' => 'Oui',
            'q17' => 'Prévoir les difficultés, savoir y faire face',
            'q18' => 'Une envie de connaître',
            'q33' => ['Au démarrage' => ['Appuyé', 'Intermédiaire']],
            'q51' => 'Au démarrage en côte.',
        ];

        $this->putJson($base, [
            'answers' => $answers,
            'current_question' => 51,
            'final' => ['score' => 72, 'lessons_proposed' => 28, 'guidance' => 'Prévoir une reprise des démarrages.'],
            'validated' => true,
        ])->assertOk()
            ->assertJsonPath('data.evaluation.answers.q51', 'Au démarrage en côte.')
            ->assertJsonPath('data.evaluation.current_question', 51)
            ->assertJsonPath('data.evaluation.status', 'validated')
            ->assertJsonPath('data.evaluation.final.score', 72)
            ->assertJsonPath('data.evaluation.final.lessons_proposed', 28);

        $this->assertSame($documentCount + 1, DocumentEvaluation::count());
        $this->assertSame($balanceBefore, (int) DB::table('wallets')->value('balance_minutes'));

        $fiche = $this->getJson('/api/monitor/formation/'.$this->student->id.'/initial-evaluation')->assertOk();
        $this->assertSame('Devenir conducteur professionnel', $fiche->json('data.evaluation.data.student.profession'));
        $this->assertTrue($fiche->json('data.evaluation.data.student.wears_correction'));
        $this->assertTrue($fiche->json('data.evaluation.data.training.manual'));
        $this->assertTrue($fiche->json('data.evaluation.data.training.accompanied_driving'));
        $this->assertSame(72, $fiche->json('data.evaluation.data.results.score'));
        $this->assertSame(28, $fiche->json('data.evaluation.data.results.lessons_proposed'));

        $otherStudent = Student::create(['user_id' => 2]);
        $this->getJson('/api/monitor/formation/'.$otherStudent->id.'/initial-evaluation/questions')->assertForbidden();

        $evaluation = DocumentEvaluation::firstOrFail();
        $stored = $evaluation->data;
        $stored['formation_signature'] = ['signed_at' => now()->toIso8601String()];
        $evaluation->update(['data' => $stored]);
        $this->putJson($base, ['answers' => $answers, 'current_question' => 51])->assertStatus(423);
    }
}