<?php

namespace Tests\Feature;

use App\Models\RdvPermisToken;
use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RdvPermisTokenPersistenceRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'rdvpermis_token_recovery_test');
        config()->set('database.connections.rdvpermis_token_recovery_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('rdvpermis_token_recovery_test');
        DB::setDefaultConnection('rdvpermis_token_recovery_test');
        Log::spy();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('status')->default(1);
        });
        (require database_path('migrations/2026_08_22_000000_create_rdvpermis_tokens_table.php'))->up();
        Schema::rename('rdvpermis_tokens', (new RdvPermisToken)->getTable());
        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    }

    protected function tearDown(): void
    {
        DB::purge('rdvpermis_token_recovery_test');

        parent::tearDown();
    }

    public function test_it_stores_new_access_and_refresh_tokens_encrypted_at_rest(): void
    {
        $stored = app(TokenService::class)->store($this->user(1), $this->freshPayload());
        $raw = DB::table($stored->getTable())->where('user_id', 1)->first();

        $this->assertSame('new-access-token', $stored->access_token);
        $this->assertSame('new-refresh-token', $stored->refresh_token);
        $this->assertNotSame('new-access-token', $raw->access_token);
        $this->assertNotSame('new-refresh-token', $raw->refresh_token);
        $this->assertSame('new-access-token', decrypt($raw->access_token, false));
        $this->assertSame('new-refresh-token', decrypt($raw->refresh_token, false));
    }

    public function test_it_updates_a_valid_existing_token_and_preserves_a_valid_refresh_token_when_omitted(): void
    {
        $existing = RdvPermisToken::query()->create([
            'user_id' => 1,
            'access_token' => 'old-access-token',
            'refresh_token' => 'old-refresh-token',
            'status' => 'reconnect_required',
        ]);

        $stored = app(TokenService::class)->store($this->user(1), [
            'access_token' => 'replacement-access-token',
            'expires_in' => 300,
        ]);

        $this->assertSame($existing->getKey(), $stored->getKey());
        $this->assertSame('replacement-access-token', $stored->access_token);
        $this->assertSame('old-refresh-token', $stored->refresh_token);
        $this->assertSame('connected', $stored->status);
    }

    public function test_a_corrupt_access_token_is_deleted_and_recreated_with_fresh_encrypted_credentials(): void
    {
        $existing = RdvPermisToken::query()->create([
            'user_id' => 1,
            'access_token' => 'old-access-token',
            'refresh_token' => 'valid-refresh-token',
            'status' => 'connected',
        ]);
        DB::table($existing->getTable())->where('id', $existing->getKey())->update([
            'access_token' => $this->ciphertextFromAnotherKey('old-access-token'),
        ]);

        $stored = app(TokenService::class)->store($this->user(1), $this->freshPayload());

        $this->assertNotSame($existing->getKey(), $stored->getKey());
        $this->assertSame('new-access-token', $stored->access_token);
        $this->assertSame('new-refresh-token', $stored->refresh_token);

        Log::shouldHaveReceived('warning')->once()->with(
            'RdvPermis stale encrypted credentials detected',
            Mockery::on(fn (array $context) => $context['user_id'] === 1
                && $context['exception'] === DecryptException::class
                && ! str_contains(json_encode($context), 'new-access-token')
                && ! str_contains(json_encode($context), 'old-access-token')),
        );
    }

    public function test_a_corrupt_refresh_token_is_deleted_and_recreated_for_only_the_affected_user(): void
    {
        $stale = RdvPermisToken::query()->create([
            'user_id' => 1,
            'access_token' => 'old-access-token',
            'refresh_token' => 'old-refresh-token',
            'status' => 'connected',
        ]);
        $other = RdvPermisToken::query()->create([
            'user_id' => 2,
            'access_token' => 'other-access-token',
            'refresh_token' => 'other-refresh-token',
            'status' => 'connected',
        ]);
        $otherRawAccessToken = DB::table($other->getTable())->where('id', $other->getKey())->value('access_token');
        $staleCiphertext = $this->ciphertextFromAnotherKey('old-refresh-token');
        DB::table($stale->getTable())->where('id', $stale->getKey())->update(['refresh_token' => $staleCiphertext]);
        $this->assertSame(
            $staleCiphertext,
            DB::table($stale->getTable())->where('id', $stale->getKey())->value('refresh_token'),
        );

        $stored = app(TokenService::class)->store($this->user(1), [
            'access_token' => 'replacement-access-token',
            'expires_in' => 300,
        ]);

        $this->assertNotSame($stale->getKey(), $stored->getKey());
        $this->assertSame('replacement-access-token', $stored->access_token);
        $this->assertNull($stored->refresh_token);
        $this->assertSame(2, RdvPermisToken::count());
        $this->assertSame($otherRawAccessToken, DB::table($other->getTable())->where('id', $other->getKey())->value('access_token'));
        $this->assertSame('other-access-token', RdvPermisToken::query()->findOrFail($other->getKey())->access_token);

        Log::shouldHaveReceived('warning')->once()->with(
            'RdvPermis stale encrypted credentials detected',
            Mockery::on(fn (array $context) => $context['user_id'] === 1
                && $context['exception'] === DecryptException::class
                && ! str_contains(json_encode($context), 'replacement-access-token')
                && ! str_contains(json_encode($context), 'old-refresh-token')),
        );
    }

    private function user(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }

    private function freshPayload(): array
    {
        return [
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 300,
            'refresh_expires_in' => 1800,
            'scope' => 'rdvpermis offline_access',
        ];
    }

    private function ciphertextFromAnotherKey(string $value): string
    {
        $cipher = config('app.cipher', 'AES-256-CBC');
        $keyLength = str_contains($cipher, '128') ? 16 : 32;

        return (new Encrypter(random_bytes($keyLength), $cipher))->encrypt($value, false);
    }
}
