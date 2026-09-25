<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RdvPermis\ApiClient;
use App\Services\RdvPermis\PanierService;
use App\Services\RdvPermis\PlanningService;
use Illuminate\Http\Client\Response;
use Mockery;
use Tests\TestCase;

class RdvPermisPanierServiceTest extends TestCase
{
    public function test_planning_search_uses_the_verified_nested_filter_schema(): void
    {
        $user = new User;
        $groupePermis = 'A';
        $date = '2026-09-29';
        $centreId = '264af7a0-bdc6-49b3-a567-bf4e09f55d64';
        $response = Mockery::mock(Response::class);
        $client = Mockery::mock(ApiClient::class);
        $client->shouldReceive('post')->once()->with($user, '/api/v2/auto-ecole/planning/recherche', [
            'filtre' => [
                'groupePermis' => $groupePermis,
                'date' => $date,
                'centreId' => $centreId,
            ],
        ])->andReturn($response);

        $this->assertSame($response, (new PlanningService($client))->search($user, $groupePermis, $date, $centreId));
    }

    public function test_assignment_contract_sends_only_candidat_id_and_allows_null_removal(): void
    {
        $user = new User;
        $response = Mockery::mock(Response::class);
        $client = Mockery::mock(ApiClient::class);
        $client->shouldReceive('put')->once()->with(
            $user,
            '/api/v2/auto-ecole/paniers/panier-1/creneaux/creneau-1/candidat',
            ['candidatId' => null],
        )->andReturn($response);

        $this->assertSame($response, (new PanierService($client))->assignCandidate($user, 'panier-1', 'creneau-1', null));
    }
}
