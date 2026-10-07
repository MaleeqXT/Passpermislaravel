<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

abstract class RdvPermisDatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        config()->set('rdvpermis.cache_store', 'array');
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.token_url', 'https://auth.example.test/token');
        config()->set('rdvpermis.client_id', 'fixture-client');
        config()->set('rdvpermis.client_secret', 'fixture-secret');
        config()->set('rdvpermis.timeout', 2);
        Log::spy();
        Http::preventStrayRequests();
        $this->migration('2024_12_24_000006_create_users_table');
        DB::table('users')->insert([
            'id' => 1, 'first_name' => 'Louis', 'last_name' => 'Dupont',
            'name' => 'Louis Dupont', 'email' => 'louis@example.test', 'password' => 'fixture',
        ]);
    }

    protected function migration(string $name): void
    {
        (require database_path('migrations/'.$name.'.php'))->up();
    }

    protected function tokenSchema(): void
    {
        $this->migration('2026_08_22_000000_create_rdvpermis_tokens_table');
        $this->migration('2026_10_07_000001_normalize_rdvpermis_token_table');
    }

    protected function actor(): User
    {
        $user = new User;
        $user->id = 1;

        return $user;
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }
}
