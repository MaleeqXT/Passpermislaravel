<?php

namespace Tests\Feature;

use App\Http\Requests\V1\Student\User\Info\RegisterStudentRequest;
use App\Http\Requests\V1\Student\User\Info\StoreStudentRequest;
use App\Http\Requests\V1\Student\User\Info\UpdateStudentRequest;
use App\Http\Requests\V1\Student\User\Params\UpdateNephRequest;
use App\Models\RdvPermisSyncRecord;
use App\Models\RdvPermisToken;
use App\Services\RdvPermis\RdvPermisSyncService;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\RdvPermisDatabaseTestCase;

class RdvPermisFreshMigrationTest extends RdvPermisDatabaseTestCase
{
    public function test_laravel_runs_fresh_migrations_and_second_run_is_a_noop(): void
    {
        DB::purge('sqlite'); // Empty isolated database, including no migration history.
        $names = [
            '2024_12_24_000006_create_users_table',
            '2024_12_31_200006_create_students_table',
            '2026_08_22_000000_create_rdvpermis_tokens_table',
            '2026_08_22_000001_create_rdvpermis_sync_records_table',
            '2026_09_24_000000_create_rdvpermis_oauth_states_table',
            '2026_10_07_000001_normalize_rdvpermis_token_table',
            '2026_10_07_000002_store_neph_as_text',
            '2026_10_07_000003_add_rdvpermis_candidate_mapping',
        ];
        $options = ['--database' => 'sqlite', '--path' => array_map(fn ($name) => 'database/migrations/'.$name.'.php', $names), '--force' => true];
        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('migrate', $options));
        $this->assertSame(8, DB::table('migrations')->count());
        $this->assertTrue(Schema::hasTable((new RdvPermisToken)->getTable()));
        $this->assertTrue(Schema::hasTable((new RdvPermisSyncRecord)->getTable()));
        $this->assertSame('text', Schema::getColumnType('students', 'neph'));
        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('migrate', $options));
        $this->assertSame(8, DB::table('migrations')->count());
    }

    public function test_fresh_token_migrations_match_the_model_and_encrypt_both_credentials(): void
    {
        $this->tokenSchema();
        $token = app(TokenService::class)->store($this->actor(), [
            'access_token' => 'fixture-access', 'refresh_token' => 'fixture-refresh', 'expires_in' => 300,
        ]);
        $this->assertSame('rdvpermis_tokens', $token->getTable());
        $this->assertFalse(Schema::hasTable('rdv_permis_tokens'));
        $raw = DB::table('rdvpermis_tokens')->sole();
        $this->assertTrue($raw->access_token !== $token->access_token);
        $this->assertTrue($raw->refresh_token !== $token->refresh_token);
        $this->assertTrue(decrypt($raw->access_token, false) === 'fixture-access');
        $this->assertTrue(decrypt($raw->refresh_token, false) === 'fixture-refresh');
    }

    public function test_existing_legacy_table_is_renamed_without_changing_any_ciphertext(): void
    {
        $this->migration('2026_08_22_000000_create_rdvpermis_tokens_table');
        app(TokenService::class)->store($this->actor(), [
            'access_token' => 'fixture-access', 'refresh_token' => 'fixture-refresh', 'expires_in' => 300,
        ]);
        $before = (array) DB::table('rdvpermis_tokens')->sole();
        // Explicitly simulate the inspected deployed legacy table, only in this compatibility test.
        Schema::rename('rdvpermis_tokens', 'rdv_permis_tokens');
        $this->migration('2026_10_07_000001_normalize_rdvpermis_token_table');
        $this->assertTrue($before === (array) DB::table('rdvpermis_tokens')->sole());
        $this->assertTrue(RdvPermisToken::query()->sole()->refresh_token === 'fixture-refresh');
        (require database_path('migrations/2026_10_07_000001_normalize_rdvpermis_token_table.php'))->down();
        $this->assertSame(1, RdvPermisToken::count());
    }

    public function test_ambiguous_dual_tables_stop_without_deleting_either_table(): void
    {
        $this->tokenSchema();
        app(TokenService::class)->store($this->actor(), ['access_token' => 'fixture', 'expires_in' => 300]);
        Schema::rename('rdvpermis_tokens', 'rdv_permis_tokens');
        // SQLite index names are global, unlike MySQL's per-table index names.
        Schema::table('rdv_permis_tokens', fn (\Illuminate\Database\Schema\Blueprint $table) => $table->renameIndex(
            'rdvpermis_tokens_user_id_unique', 'legacy_tokens_user_id_unique',
        ));
        Schema::table('rdv_permis_tokens', fn (\Illuminate\Database\Schema\Blueprint $table) => $table->renameIndex(
            'rdvpermis_tokens_status_index', 'legacy_tokens_status_index',
        ));
        $this->migration('2026_08_22_000000_create_rdvpermis_tokens_table');
        try {
            $this->migration('2026_10_07_000001_normalize_rdvpermis_token_table');
            $this->fail('Ambiguous tables must be reconciled explicitly.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no records were changed', $exception->getMessage());
        }
        $this->assertSame(1, DB::table('rdv_permis_tokens')->count());
        $this->assertSame(0, DB::table('rdvpermis_tokens')->count());
    }

    public function test_real_student_migration_preserves_existing_numeric_data_and_new_identifiers(): void
    {
        $this->migration('2024_12_31_200006_create_students_table');
        DB::table('students')->insert(['id' => 'existing', 'user_id' => 1, 'neph' => 123456789012]);
        DB::table('students')->insert(['id' => 'missing', 'user_id' => 1, 'neph' => null]);
        $this->migration('2026_10_07_000002_store_neph_as_text');
        $this->assertSame('123456789012', DB::table('students')->where('id', 'existing')->value('neph'));
        $this->assertNull(DB::table('students')->where('id', 'missing')->value('neph'));
        foreach (['01234567890909', str_repeat('0', 300).'123', 'provider-identifier'] as $i => $neph) {
            DB::table('students')->insert(['id' => 'new-'.$i, 'user_id' => 1, 'neph' => $neph]);
            $this->assertSame($neph, DB::table('students')->where('id', 'new-'.$i)->value('neph'));
        }
        (require database_path('migrations/2026_10_07_000002_store_neph_as_text.php'))->down();
        $this->assertSame('01234567890909', DB::table('students')->where('id', 'new-0')->value('neph'));
    }

    #[DataProvider('nephRules')]
    public function test_neph_rules_match_the_nonempty_string_contract(string $requestClass, mixed $value, bool $valid): void
    {
        $this->migration('2024_12_31_200006_create_students_table');
        $this->migration('2026_10_07_000002_store_neph_as_text');
        $request = new $requestClass;
        $route = new \Illuminate\Routing\Route('PUT', '/students/{student}', fn () => null);
        $route->bind($request);
        $route->setParameter('student', (new \App\Models\Roles\Student\User\Student)->forceFill(['user_id' => 1]));
        $request->setRouteResolver(fn () => $route);
        $validator = Validator::make(['neph' => $value], ['neph' => $request->rules()['neph']]);
        $this->assertSame($valid, $validator->passes());
    }

    public static function nephRules(): iterable
    {
        foreach ([StoreStudentRequest::class, UpdateStudentRequest::class, RegisterStudentRequest::class, UpdateNephRequest::class] as $request) {
            foreach (['123456789012', '01234567890909', str_repeat('0', 300).'123', 'provider-identifier'] as $value) {
                yield $request.'/'.$value => [$request, $value, true];
            }
            yield $request.'/missing' => [$request, null, $request !== UpdateNephRequest::class];
            yield $request.'/numeric' => [$request, 123456789012, false];
            yield $request.'/array' => [$request, ['invalid'], false];
        }
    }

    public function test_sync_model_uses_real_migration_and_incomplete_results_preserve_mapping(): void
    {
        $this->migration('2026_08_22_000001_create_rdvpermis_sync_records_table');
        $this->migration('2026_10_07_000003_add_rdvpermis_candidate_mapping');
        $sync = app(RdvPermisSyncService::class);
        $record = $sync->recordFor('student_rdvpermis_mandate', 'local-student');
        $sync->markAttempt($record);
        $sync->markSynced($record, 'mandate-id', [
            'remote_candidate_id' => 'candidate-id', 'permit_group' => 'B',
            'remote_school_id' => 'school-id', 'provider_context' => ['environment' => 'recette1'],
        ]);
        $sync->markSynced($record, '', ['remote_candidate_id' => null, 'remote_school_id' => '', 'provider_context' => []]);
        $record = RdvPermisSyncRecord::query()->sole();
        $this->assertSame('rdvpermis_sync_records', $record->getTable());
        $this->assertSame('mandate-id', $record->remote_id);
        $this->assertSame('candidate-id', $record->remote_candidate_id);
        $this->assertSame('school-id', $record->remote_school_id);
        $this->assertSame('B', $record->permit_group);
        $this->assertSame(['environment' => 'recette1'], $record->provider_context);
        $this->assertNotNull($record->synced_at);
        (require database_path('migrations/2026_10_07_000003_add_rdvpermis_candidate_mapping.php'))->down();
        $this->migration('2026_10_07_000003_add_rdvpermis_candidate_mapping');
        $this->assertSame('candidate-id', RdvPermisSyncRecord::query()->sole()->remote_candidate_id);
    }
}
