<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisPanierSlotApiTest extends TestCase
{
    private const PANIER_ID = 'panier-1';

    private const CRENEAU_ID = 'creneau-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 2);
        Http::preventStrayRequests();
    }

    public function test_add_slot_uses_the_verified_body_and_optional_query_parameter(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $url = 'https://api.example.test/api/v2/auto-ecole/paniers/'.self::PANIER_ID.'/creneaux?inclureEstCandidatObligatoire=1';
        $fixture = ['id' => self::PANIER_ID, 'elementsDuPanier' => []];
        Http::fake([$url => Http::response($fixture, 201)]);

        $this->postJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/creneaux', [
            'creneauId' => self::CRENEAU_ID,
            'inclureEstCandidatObligatoire' => true,
        ])->assertCreated()->assertExactJson($fixture);

        Http::assertSent(fn ($request) => $request->url() === $url && $request->data() === ['creneauId' => self::CRENEAU_ID]);
    }

    public function test_bulk_add_forwards_documented_207_business_result(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $url = 'https://api.example.test/api/v2/auto-ecole/paniers/'.self::PANIER_ID.'/creneaux-multiples';
        $fixture = [
            'crenauxIdsAjoutesAuPanierAvecSucces' => ['creneau-1'],
            'crenauxIdsAjoutesAuPanierEnEchec' => ['creneau-2'],
            'panier' => ['id' => self::PANIER_ID],
        ];
        Http::fake([$url => Http::response($fixture, 207)]);

        $this->postJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/creneaux-multiples', [
            'creneauxId' => ['creneau-1', 'creneau-2'],
        ])->assertStatus(207)->assertExactJson($fixture);
    }

    public function test_remove_slot_and_assign_or_remove_candidate_use_verified_contracts(): void
    {
        $user = $this->signIn();
        $this->bindToken($user, 3);
        $base = 'https://api.example.test/api/v2/auto-ecole/paniers/'.self::PANIER_ID.'/creneaux/'.self::CRENEAU_ID;
        Http::fake([
            $base => Http::response('', 204),
            $base.'/candidat' => Http::sequence()->push('', 204)->push('', 204),
        ]);

        $this->deleteJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/creneaux/'.self::CRENEAU_ID)->assertNoContent();
        $this->putJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/creneaux/'.self::CRENEAU_ID.'/candidat', ['candidatId' => 'candidate-1'])->assertNoContent();
        $this->putJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/creneaux/'.self::CRENEAU_ID.'/candidat', ['candidatId' => null])->assertNoContent();

        Http::assertSent(fn ($request) => $request->url() === $base.'/candidat' && $request->data() === ['candidatId' => null]);
    }

    public function test_mandated_candidate_search_preserves_candidate_id_mandate_and_eligibility_data(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $url = 'https://api.example.test/api/v2/auto-ecole/candidats-mandats/recherche';
        $request = ['filtre' => ['groupePermis' => 'B', 'numeroDossier' => ['query' => '0123', 'match' => 'PARTIAL']], 'page' => 1, 'parPage' => 25];
        $fixture = ['data' => [[
            'candidat' => ['id' => 'candidate-1', 'numeroDossier' => '01234567890909'],
            'mandat' => ['candidatId' => 'candidate-1', 'groupePermis' => 'B'],
            'penalites' => [], 'detailsResultats' => [],
        ]], 'pagination' => ['pageCourante' => 1]];
        Http::fake([$url => Http::response($fixture)]);

        $this->postJson('/api/rdvpermis/candidats-mandats/recherche', $request)->assertOk()->assertExactJson($fixture);
        Http::assertSent(fn ($sent) => $sent->url() === $url && $sent->data() === $request);
    }

    public function test_local_slot_and_candidate_validation_prevents_provider_calls(): void
    {
        $this->signIn();
        Http::fake();

        $this->postJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/creneaux', [])->assertUnprocessable()->assertJsonValidationErrors('creneauId');
        $this->putJson('/api/rdvpermis/paniers/'.self::PANIER_ID.'/creneaux/'.self::CRENEAU_ID.'/candidat', [])->assertUnprocessable()->assertJsonValidationErrors('candidatId');
        $this->postJson('/api/rdvpermis/candidats-mandats/recherche', ['filtre' => ['groupePermis' => 'BA']])->assertUnprocessable()->assertJsonValidationErrors('filtre.groupePermis');
        Http::assertNothingSent();
    }

    private function signIn(): User
    {
        $user = new User;
        $user->id = 'test-admin';
        $user->setRelation('roles', collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);

        return $user;
    }

    private function bindToken(User $user, int $times = 1): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->times($times)->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
    }
}
