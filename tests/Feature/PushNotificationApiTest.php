<?php

namespace Tests\Feature;

use Tests\TestCase;

class PushNotificationApiTest extends TestCase
{
    public function test_push_device_endpoints_require_authentication(): void
    {
        $this->getJson('/api/push/devices')->assertUnauthorized();
        $this->postJson('/api/push/register', ['fcm_token' => str_repeat('a', 32)])->assertUnauthorized();
        $this->deleteJson('/api/push/unregister', ['fcm_token' => str_repeat('a', 32)])->assertUnauthorized();
    }
}
