<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RdvPermis\TokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RdvPermisTrainerThresholdAndExamRegistrationTest extends TestCase
{
    private const REGISTER_URL = 'https://api.example.test/api/v1/auto-ecole/examen-programme/exam-1/candidat/candidate-1/validation';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('rdvpermis.api_url', 'https://api.example.test');
        config()->set('rdvpermis.timeout', 2);
        Http::preventStrayRequests();
        Log::spy();
    }

    public function test_trainer_threshold_uses_the_swagger_query_parameters(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        $threshold = [
            'date' => '2026-12-01',
            'nombreDeFormateurs' => 4.5,
            'nombreExamensReservablesAvantSeuil' => 25,
            'nombrePlacesReservees' => 25,
            'seuilNombreExamensParMois' => 50,
            'estSeuilAtteint' => false,
            'estPlafondAtteint' => false,
            'estPrioriteDepartementale' => false,
        ];
        $url = 'https://api.example.test/api/v2/auto-ecole/informations-seuil-departement?date=2026-12-01&code-departement=034&groupe-permis=B';
        Http::fake([$url => Http::response($threshold)]);

        $this->getJson('/api/rdvpermis/seuil-formateur?date=2026-12-01&codeDepartement=034&groupePermis=B')
            ->assertOk()->assertExactJson($threshold);

        Http::assertSent(fn ($request) => $request->method() === 'GET' && $request->url() === $url);
    }

    public function test_invalid_trainer_threshold_input_does_not_call_rdvpermis(): void
    {
        $this->signIn();
        Http::fake();

        foreach ([
            'codeDepartement=034&groupePermis=B',
            'date=01/12/2026&codeDepartement=034&groupePermis=B',
            'date=2026-12-01&groupePermis=B',
            'date=2026-12-01&codeDepartement=034',
            'date=2026-12-01&codeDepartement=034&groupePermis=CE',
        ] as $query) {
            $this->getJson('/api/rdvpermis/seuil-formateur?'.$query)->assertUnprocessable();
        }

        Http::assertNothingSent();
    }

    public function test_trainer_threshold_department_error_is_mapped(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        Http::fake(['https://api.example.test/api/v2/auto-ecole/informations-seuil-departement*' => Http::response(
            ['code' => 'CODE_DEPARTEMENT_INVALIDE'], 400,
        )]);

        $this->getJson('/api/rdvpermis/seuil-formateur?date=2026-12-01&codeDepartement=999&groupePermis=A')
            ->assertStatus(400)
            ->assertExactJson(['message' => 'Le département est invalide ou inactif dans RdvPermis.']);
    }

    public function test_exam_candidate_registration_uses_the_v1_put_path_without_a_body(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        Http::fake([self::REGISTER_URL => Http::response('', 200)]);

        $this->putJson('/api/rdvpermis/examens/exam-1/candidat/candidate-1/validation', ['ignored' => true])
            ->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === self::REGISTER_URL
            && $request->body() === '');
    }

    public function test_exam_candidate_registration_business_error_is_mapped_and_not_retried(): void
    {
        $user = $this->signIn();
        $this->bindToken($user);
        Http::fake([self::REGISTER_URL => Http::response(['code' => 'CATEGORIE_PERMIS_NON_GEREE'], 422)]);

        $this->putJson('/api/rdvpermis/examens/exam-1/candidat/candidate-1/validation')
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'Cette opération n’est possible que pour un examen du groupe A.']);

        Http::assertSentCount(1);
    }

    private function signIn(): User
    {
        $user = new User;
        $user->id = 'test-admin';
        $user->setRelation('roles', collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]));
        Sanctum::actingAs($user);

        return $user;
    }

    private function bindToken(User $user): void
    {
        $tokens = Mockery::mock(TokenService::class);
        $tokens->shouldReceive('accessToken')->once()->with($user)->andReturn('fake-access-token');
        $this->app->instance(TokenService::class, $tokens);
    }
}
