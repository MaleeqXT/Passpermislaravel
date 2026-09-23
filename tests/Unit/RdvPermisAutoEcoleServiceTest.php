<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RdvPermis\ApiClient;
use App\Services\RdvPermis\AutoEcoleService;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class RdvPermisAutoEcoleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
        Http::preventStrayRequests();
    }

    public function test_it_calls_only_the_confirmed_v2_current_school_endpoint(): void
    {
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://api.example.test/api/v2/auto-ecole/moi' => Http::response(['id' => 'school-1']),
        ]);

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->andReturn('stored-token');
        $user = new User;
        $user->id = 10;

        $response = (new AutoEcoleService(new ApiClient($tokens)))->currentSchool($user);

        $this->assertSame('school-1', $response->json('id'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->body() === ''
            && $request->hasHeader('Accept', 'application/json')
            && $request->url() === 'https://api.example.test/api/v2/auto-ecole/moi'
            && $request->hasHeader('Authorization', 'Bearer stored-token'));
    }

    public function test_it_calls_only_the_confirmed_v2_employees_endpoint(): void
    {
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://api.example.test/api/v2/auto-ecole/employes' => Http::response([['id' => 'employee-1']]),
        ]);

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->andReturn('stored-token');
        $user = new User;
        $user->id = 10;

        $response = (new AutoEcoleService(new ApiClient($tokens)))->employees($user);

        $this->assertSame('employee-1', $response->json('0.id'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->body() === ''
            && $request->hasHeader('Accept', 'application/json')
            && $request->url() === 'https://api.example.test/api/v2/auto-ecole/employes'
            && $request->hasHeader('Authorization', 'Bearer stored-token'));
    }
}
