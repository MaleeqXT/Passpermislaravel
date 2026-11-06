<?php

namespace Tests\Feature;

use App\Jobs\SyncLivretLessonJob;
use App\Jobs\SyncLivretTrainingContractJob;
use App\Models\LivretLesson;
use App\Models\LivretTrainingContract;
use App\Models\Roles\Student\User\Student;
use App\Services\LivretNumerique\LessonService;
use App\Services\LivretNumerique\LivretProviderGateway;
use App\Services\LivretNumerique\LivretSyncService;
use App\Services\LivretNumerique\ProviderSchemaUnavailable;
use App\Services\LivretNumerique\TrainingContractService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\RdvPermisDatabaseTestCase;

class LivretNumeriqueLocalWorkflowTest extends RdvPermisDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->migration('2024_12_31_200006_create_students_table');
        $this->migration('2026_11_06_000000_create_livret_numerique_tables');
        Student::query()->create(['id' => '11111111-1111-4111-8111-111111111111', 'user_id' => 1, 'neph' => '123456789012']);
        Queue::fake();
        Http::fake();
    }

    public function test_a_valid_contract_is_persisted_and_queued_without_a_provider_call(): void
    {
        $contract = app(TrainingContractService::class)->create($this->actor(), $this->contractData());

        $this->assertSame('pending', $contract->sync_status);
        Queue::assertPushed(SyncLivretTrainingContractJob::class, fn ($job) => $job->contractId === $contract->id);
        Http::assertNothingSent();
    }

    public function test_duplicate_contracts_are_prevented_by_the_local_unique_constraint(): void
    {
        app(TrainingContractService::class)->create($this->actor(), $this->contractData());
        $this->expectException(\Illuminate\Database\QueryException::class);
        app(TrainingContractService::class)->create($this->actor(), $this->contractData());
    }

    public function test_lessons_require_a_synced_contract_and_the_required_mapping(): void
    {
        $contract = LivretTrainingContract::query()->create([...$this->contractData(), 'sync_user_id' => 1, 'sync_status' => 'pending']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(LessonService::class)->create($this->actor(), $this->lessonData($contract));
    }

    public function test_completed_lesson_is_queued_only_when_contract_is_synced(): void
    {
        $contract = LivretTrainingContract::query()->create([...$this->contractData(), 'sync_user_id' => 1, 'sync_status' => 'synced']);
        $lesson = app(LessonService::class)->create($this->actor(), $this->lessonData($contract));

        $this->assertSame('pending', $lesson->sync_status);
        Queue::assertPushed(SyncLivretLessonJob::class, fn ($job) => $job->lessonId === $lesson->id);
        Http::assertNothingSent();
    }

    public function test_unverified_gateway_marks_a_contract_for_correction_and_never_issues_http(): void
    {
        Queue::fake();
        $contract = LivretTrainingContract::query()->create([...$this->contractData(), 'sync_user_id' => 1]);
        $sync = app(LivretSyncService::class);
        $sync->attempt($contract, 'create_contract');
        try { app(LivretProviderGateway::class)->createTrainingContract($this->actor(), $contract); } catch (ProviderSchemaUnavailable $error) { $sync->failed($contract, 'create_contract', $error); }

        $contract->refresh();
        $this->assertSame('needs_resync', $contract->sync_status);
        $this->assertSame('UNVERIFIED_PROVIDER_SCHEMA', $contract->provider_error_code);
        Http::assertNothingSent();
    }

    private function contractData(): array
    {
        return ['student_id' => '11111111-1111-4111-8111-111111111111', 'request_number' => '123456789012', 'registration_date' => '2026-01-15', 'training_type' => 'traditionnelle', 'permit_category' => 'B', 'vehicle_type' => 'manuelle', 'evaluated_hours' => 20];
    }

    private function lessonData(LivretTrainingContract $contract): array
    {
        return ['student_id' => $contract->student_id, 'training_contract_id' => $contract->id, 'lesson_date' => now()->subDay()->toDateString(), 'started_at' => now()->subDay()->setTime(10, 0), 'ended_at' => now()->subDay()->setTime(11, 0), 'duration_minutes' => 60, 'lesson_type' => 'circulation', 'permit_category' => 'B', 'vehicle_type' => 'manuelle', 'trainer_id' => 'local-trainer-1', 'provider_trainer_id' => 'provider-trainer-1', 'trainer_authorization' => 'authorization-1', 'department_code' => '075'];
    }
}
