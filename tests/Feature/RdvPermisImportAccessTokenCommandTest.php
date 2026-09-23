<?php

namespace Tests\Feature;

use App\Console\Commands\ImportRdvPermisAccessToken;
use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\RdvPermisToken;
use App\Models\User;
use App\Services\RdvPermis\AutoEcoleService;
use App\Services\RdvPermis\TokenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\TestCase;

class RdvPermisImportAccessTokenCommandTest extends TestCase
{
    private const COMMAND = 'rdvpermis:import-access-token';

    private const PROMPT = 'RDVPermis access token (hidden; paste without Bearer)';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        config()->set('database.default', 'rdvpermis_command_test');
        config()->set('database.connections.rdvpermis_command_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        Log::spy();
        // Changing the test environment to local enables framework deprecation reporting.
        Log::shouldReceive('channel')->with('deprecations')->andReturn(new NullLogger);
        Http::preventStrayRequests();
        Http::fake([]);
        $this->freezeTime();

        // Only an isolated in-memory DB is used; do not migrate or connect to the application's DB.
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->integer('status')->default(SituationStatusEnum::ACTIVE->value);
            $table->softDeletes();
        });
        (require database_path('migrations/2026_08_22_000000_create_rdvpermis_tokens_table.php'))->up();
        // The existing local DB uses the model's inferred table name, unlike the old migration.
        // Adapt only this memory fixture; do not alter the application model or real DB.
        Schema::rename('rdvpermis_tokens', (new RdvPermisToken)->getTable());
        DB::table('users')->insert([['id' => '1'], ['id' => '2']]);
    }

    protected function tearDown(): void
    {
        DB::purge('rdvpermis_command_test');
        parent::tearDown();
    }

    public function test_it_imports_encrypted_credentials_and_the_existing_api_client_uses_them(): void
    {
        $secret = 'fake-swagger-access-token';
        $this->artisan(self::COMMAND, ['user_id' => '1', '--expires-in' => '120'])
            ->expectsQuestion(self::PROMPT, $secret)
            ->expectsOutput('RDVPermis access token stored encrypted for user 1.')
            ->doesntExpectOutputToContain($secret)
            ->assertSuccessful();

        $record = RdvPermisToken::query()->sole();
        $ciphertext = DB::table((new RdvPermisToken)->getTable())->value('access_token');
        $this->assertNotSame($secret, $ciphertext);
        $this->assertSame($secret, decrypt($ciphertext, false));
        $this->assertSame($secret, $record->access_token);
        $this->assertSame('1', (string) $record->user_id);
        $this->assertSame('connected', $record->status);
        $this->assertSame(now()->addSeconds(120)->timestamp, $record->access_token_expires_at->timestamp);
        $this->assertNull($record->refresh_token);
        $this->assertNull($record->refresh_expires_at);
        $this->assertNull($record->scopes);
        $this->assertNull($record->last_error);
        $this->assertArrayNotHasKey('access_token', $record->toArray());
        Http::assertNothingSent();
        $this->assertNothingLogged();

        config()->set('rdvpermis.api_url', 'https://recette.example.test');
        Http::fake(['https://recette.example.test/api/v2/auto-ecole/moi' => Http::response(['test_fixture' => true])]);
        $user = User::query()->withoutEagerLoads()->findOrFail('1');
        $this->assertTrue(app(AutoEcoleService::class)->currentSchool($user)->json('test_fixture'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://recette.example.test/api/v2/auto-ecole/moi'
            && $request->hasHeader('Authorization', 'Bearer '.$secret));
    }

    public function test_reimport_updates_only_the_selected_user_and_clears_stale_refresh_credentials(): void
    {
        $first = RdvPermisToken::create([
            'user_id' => '1', 'access_token' => 'old-access-token', 'refresh_token' => 'old-refresh-token',
            'refresh_expires_at' => now()->addDay(), 'scopes' => ['old-scope'],
            'status' => 'reconnect_required', 'last_error' => 'old-error',
        ]);
        RdvPermisToken::create(['user_id' => '2', 'access_token' => 'other-user-token', 'status' => 'connected']);

        $this->artisan(self::COMMAND, ['user_id' => '1', '--expires-in' => '60'])
            ->expectsQuestion(self::PROMPT, 'replacement-access-token')
            ->doesntExpectOutputToContain('replacement-access-token')
            ->assertSuccessful();

        $this->assertSame(2, RdvPermisToken::count());
        $first->refresh();
        $this->assertSame('replacement-access-token', $first->access_token);
        $this->assertNull($first->refresh_token);
        $this->assertNull($first->refresh_expires_at);
        $this->assertNull($first->scopes);
        $this->assertNull($first->last_error);
        $this->assertSame('connected', $first->status);
        $this->assertSame('other-user-token', RdvPermisToken::where('user_id', '2')->sole()->access_token);
        $this->assertNothingLogged();
    }

    public static function nonLocalEnvironments(): array
    {
        return [['production'], ['staging'], ['testing']];
    }

    #[DataProvider('nonLocalEnvironments')]
    public function test_it_refuses_non_local_environments_before_prompting_or_writing(string $environment): void
    {
        $this->app['env'] = $environment;
        $this->artisan(self::COMMAND, ['user_id' => '1'])
            ->expectsOutput('This command is available only in the local environment.')
            ->assertFailed();
        $this->assertSame(0, RdvPermisToken::count());
        Http::assertNothingSent();
    }

    public function test_it_refuses_non_interactive_execution(): void
    {
        $this->artisan(self::COMMAND, ['user_id' => '1', '--no-interaction' => true])
            ->expectsOutput('An interactive terminal with hidden input is required.')
            ->assertFailed();
        $this->assertSame(0, RdvPermisToken::count());
    }

    public function test_it_refuses_unknown_or_deleted_users_before_prompting(): void
    {
        DB::table('users')->where('id', '2')->update(['deleted_at' => now()]);
        foreach (['missing-user', '2'] as $id) {
            $this->artisan(self::COMMAND, ['user_id' => $id])
                ->expectsOutput('Laravel user not found.')
                ->assertFailed();
        }
        $this->assertSame(0, RdvPermisToken::count());
    }

    public static function invalidExpiries(): array
    {
        return [['0'], ['-1'], ['invalid'], ['1.5'], ['86401']];
    }

    #[DataProvider('invalidExpiries')]
    public function test_it_rejects_invalid_expiry_before_prompting(string $expiry): void
    {
        $this->artisan(self::COMMAND, ['user_id' => '1', '--expires-in' => $expiry])
            ->expectsOutput('The --expires-in option must be an integer between 1 and 86400 seconds.')
            ->assertFailed();
        $this->assertSame(0, RdvPermisToken::count());
    }

    public static function invalidTokens(): array
    {
        return [[''], ['Bearer sensitive-token'], ["sensitive-token\nsecond-line"]];
    }

    #[DataProvider('invalidTokens')]
    public function test_it_rejects_invalid_input_without_printing_or_storing_it(string $token): void
    {
        $this->artisan(self::COMMAND, ['user_id' => '1'])
            ->expectsQuestion(self::PROMPT, $token)
            ->expectsOutput('Enter a non-empty access token without spaces, line breaks or the Bearer prefix.')
            ->doesntExpectOutputToContain('sensitive-token')
            ->assertFailed();
        $this->assertSame(0, RdvPermisToken::count());
        $this->assertNothingLogged();
    }

    public function test_persistence_errors_do_not_print_or_log_sensitive_details(): void
    {
        RdvPermisToken::saving(fn () => throw new RuntimeException('private-token-value from exception'));
        try {
            $this->artisan(self::COMMAND, ['user_id' => '1'])
                ->expectsQuestion(self::PROMPT, 'private-token-value')
                ->expectsOutput('Token import failed. Check hidden-input support and the local database/encryption configuration.')
                ->doesntExpectOutputToContain('private-token-value')
                ->assertFailed();
        } finally {
            RdvPermisToken::flushEventListeners();
        }
        $this->assertSame(0, RdvPermisToken::count());
        $this->assertNothingLogged();
    }

    public function test_expiry_requires_a_new_import_without_attempting_a_refresh_request(): void
    {
        $this->artisan(self::COMMAND, ['user_id' => '1', '--expires-in' => '60'])
            ->expectsQuestion(self::PROMPT, 'short-lived-token')
            ->assertSuccessful();
        $this->travel(61)->seconds();

        try {
            app(TokenService::class)->accessToken(User::query()->withoutEagerLoads()->findOrFail('1'));
            $this->fail('An expired manually imported token must not be reused.');
        } catch (RuntimeException) {
            $this->assertSame('reconnect_required', RdvPermisToken::query()->sole()->status);
        }
        Http::assertNothingSent();
    }

    public function test_the_command_is_discovered_without_a_token_argument_or_option(): void
    {
        $command = Artisan::all()[self::COMMAND];
        $this->assertInstanceOf(ImportRdvPermisAccessToken::class, $command);
        $this->assertFalse($command->getDefinition()->hasArgument('token'));
        $this->assertFalse($command->getDefinition()->hasOption('token'));
    }

    private function assertNothingLogged(): void
    {
        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'log'] as $level) {
            Log::shouldNotHaveReceived($level);
        }
    }
}
