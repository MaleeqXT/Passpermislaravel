<?php

namespace Tests\Unit;

use App\Services\Push\FcmPushService;
use Tests\TestCase;

class FcmPushServiceTest extends TestCase
{
    public function test_push_service_is_disabled_without_server_credentials(): void
    {
        config()->set('services.firebase.service_account_json', null);
        $this->assertFalse(app(FcmPushService::class)->configured());
    }
}
