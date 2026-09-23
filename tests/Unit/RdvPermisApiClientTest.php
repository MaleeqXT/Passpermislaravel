<?php

namespace Tests\Unit;

use App\Exceptions\RdvPermisApiException;
use App\Models\User;
use App\Services\RdvPermis\ApiClient;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Mockery;
use Tests\TestCase;

class RdvPermisApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_connection_errors_do_not_retain_sensitive_request_details(): void
    {
        Log::spy();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        Http::fake(fn () => throw new ConnectionException('Authorization: Bearer secret-token client_secret=secret-value'));

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->andReturn('secret-token');
        $tokens->shouldNotReceive('markNeedsReauthentication');

        try {
            (new ApiClient($tokens))->get(new User, '/api/v2/auto-ecole/moi');
            $this->fail('A connection failure must return a sanitized error.');
        } catch (RdvPermisApiException $exception) {
            $this->assertSame(503, $exception->responseStatus);
            $this->assertNull($exception->getPrevious());
            $this->assertStringNotContainsString('secret-token', $exception->getMessage());
            $this->assertStringNotContainsString('secret-value', $exception->getMessage());
        }

        Log::shouldHaveReceived('warning')->once()->with('RdvPermis API connection failure', Mockery::on(
            fn (array $context) => ! str_contains(json_encode($context), 'secret-token')
                && ! str_contains(json_encode($context), 'secret-value')
        ));
    }

    public function test_api_redirects_are_not_followed_even_if_options_request_them(): void
    {
        Log::spy();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        Http::fake(function ($request, array $options) {
            $this->assertFalse($options['allow_redirects']);

            return Http::response('', 302, ['Location' => 'https://other.example.test/login']);
        });

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->andReturn('stored-token');
        $tokens->shouldNotReceive('markNeedsReauthentication');

        try {
            (new ApiClient($tokens))->request(new User, 'GET', '/api/v2/auto-ecole/moi', ['allow_redirects' => true]);
            $this->fail('A redirect must be handled as an upstream error.');
        } catch (RdvPermisApiException $exception) {
            $this->assertSame(502, $exception->responseStatus);
        }

        Http::assertSentCount(1);
    }

    public function test_it_uses_a_bearer_token_and_returns_a_successful_response(): void
    {
        Log::spy();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://api.example.test/api/v2/auto-ecole/moi' => Http::response(['id' => 'school-1']),
        ]);

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->andReturn('encrypted-at-rest-token');

        $user = new User;
        $user->id = 10;

        $response = (new ApiClient($tokens))->get($user, '/api/v2/auto-ecole/moi');

        $this->assertSame('school-1', $response->json('id'));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer encrypted-at-rest-token'));
        Log::shouldHaveReceived('info')->once()->with(
            'RdvPermis API request',
            Mockery::on(fn (array $context) => $context['endpoint'] === '/api/v2/auto-ecole/moi'
                && ! str_contains(json_encode($context), 'encrypted-at-rest-token')),
        );
    }

    public function test_it_maps_upstream_errors_without_returning_the_remote_payload(): void
    {
        Log::spy();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://api.example.test/*' => Http::response(['remote_debug' => 'must not be exposed'], 403),
        ]);

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->andReturn('access-token');

        $user = new User;
        $user->id = 11;

        try {
            (new ApiClient($tokens))->get($user, '/api/v2/auto-ecole/moi');
            $this->fail('The upstream 403 response should throw an exception.');
        } catch (RdvPermisApiException $exception) {
            $this->assertSame(403, $exception->responseStatus);
            $this->assertStringNotContainsString('remote_debug', $exception->userMessage);
        }
    }

    public function test_it_marks_the_connection_for_reauthentication_after_an_upstream_401(): void
    {
        Log::spy();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 20);

        Http::fake([
            'https://api.example.test/*' => Http::response([], 401),
        ]);

        $user = new User;
        $user->id = 13;

        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($user)->andReturn('rejected-token');
        $tokens->shouldReceive('markNeedsReauthentication')->once()->with($user);

        try {
            (new ApiClient($tokens))->get($user, '/api/v2/auto-ecole/moi');
            $this->fail('The upstream 401 response should throw an exception.');
        } catch (RdvPermisApiException $exception) {
            $this->assertSame(401, $exception->responseStatus);
        }
    }
}
