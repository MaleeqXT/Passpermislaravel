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

class RdvPermisExamApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 2);
        Http::preventStrayRequests();
    }

    public function test_exam_listing_lookup_and_cancellation_use_current_v2_paths(): void
    {
        $user = $this->signIn();
        $this->bindToken($user, 3);
        $exam = ['id' => 'exam-1', 'estModifiable' => true];
        Http::fake([
            'https://api.example.test/api/v2/auto-ecole/examens?statut=PROGRAMME&groupe-permis=B&date=2026-10-20' => Http::response([$exam]),
            'https://api.example.test/api/v2/auto-ecole/examens/exam-1' => Http::sequence()->push($exam)->push('', 204),
        ]);

        $this->getJson('/api/rdvpermis/examens?groupePermis=B&date=2026-10-20')->assertOk()->assertExactJson([$exam]);
        $this->getJson('/api/rdvpermis/examens/exam-1')->assertOk()->assertExactJson($exam);
        $this->deleteJson('/api/rdvpermis/examens/exam-1')->assertNoContent();
    }

    public function test_exam_permutation_replacement_and_supporting_queries_use_verified_schemas(): void
    {
        $user = $this->signIn();
        $this->bindToken($user, 6);
        Http::fake([
            'https://api.example.test/api/v2/auto-ecole/examens/permutables?examen-permute-id=exam-1&date=2026-10-20' => Http::response([['id' => 'exam-2']]),
            'https://api.example.test/api/v2/auto-ecole/examens/nombre-de-remplacement?groupe-permis=B&date=2026-10-20' => Http::response(['remplacementsParMois' => 5, 'remplacementsRestants' => 3]),
            'https://api.example.test/api/v2/auto-ecole/examens/dates?statut=PROGRAMME&groupe-permis=B&date-debut=2026-10-20&creneauSansCandidat=1' => Http::response(['2026-10-21']),
            'https://api.example.test/api/v2/auto-ecole/examens-pagines?groupe-permis=CE&statut=PROGRAMME&date-debut=2026-10-20&page=2&par-page=10' => Http::response(['data' => [], 'pagination' => ['pageCourante' => 2]]),
            'https://api.example.test/api/v2/auto-ecole/examens/remplacement' => Http::response('', 204),
            'https://api.example.test/api/v2/auto-ecole/examens/permutation' => Http::response('', 204),
        ]);

        $this->getJson('/api/rdvpermis/examens/permutables?examenPermuteId=exam-1&date=2026-10-20')->assertOk();
        $this->getJson('/api/rdvpermis/examens/nombre-de-remplacement?groupePermis=B&date=2026-10-20')->assertOk();
        $this->getJson('/api/rdvpermis/examens/dates?groupePermis=B&dateDebut=2026-10-20&creneauSansCandidat=1')->assertOk();
        $this->getJson('/api/rdvpermis/examens/pagines?dateDebut=2026-10-20&page=2&parPage=10')->assertOk();
        $this->postJson('/api/rdvpermis/examens/remplacement', ['examenId' => 'exam-1', 'candidatRemplacantId' => 'candidate-2'])->assertNoContent();
        $this->postJson('/api/rdvpermis/examens/permutation', ['examenId1' => 'exam-1', 'examenId2' => 'exam-2'])->assertNoContent();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test/api/v2/auto-ecole/examens/remplacement'
            && $request->data() === ['examenId' => 'exam-1', 'candidatRemplacantId' => 'candidate-2']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.example.test/api/v2/auto-ecole/examens/permutation'
            && $request->data() === ['examenId1' => 'exam-1', 'examenId2' => 'exam-2']);
    }

    public function test_invalid_exam_input_does_not_call_rdvpermis(): void
    {
        $this->signIn();
        Http::fake();

        $this->getJson('/api/rdvpermis/examens?groupePermis=CE&date=2026-10-20')->assertUnprocessable();
        $this->postJson('/api/rdvpermis/examens/permutation', ['examenId1' => 'exam-1'])->assertUnprocessable();
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

    private function bindToken(User $user, int $times): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->times($times)->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
    }
}
