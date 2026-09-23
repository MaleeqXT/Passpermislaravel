<?php

namespace Tests\Unit;

use App\Models\RdvPermisSyncRecord;
use App\Services\RdvPermis\RdvPermisSyncService;
use Tests\TestCase;

class RdvPermisSyncServiceTest extends TestCase
{
    public function test_it_tracks_attempt_success_and_remote_id_without_persisting_sensitive_errors(): void
    {
        $record = new class extends RdvPermisSyncRecord
        {
            public function save(array $options = []): bool
            {
                return true;
            }
        };
        $record->sync_attempts = 0;
        $service = new RdvPermisSyncService;

        $service->markAttempt($record);
        $this->assertSame('pending', $record->status);
        $this->assertSame(1, $record->sync_attempts);
        $this->assertNotNull($record->last_sync_attempt_at);

        $service->markSynced($record, 'remote-contract-1');
        $this->assertSame('synced', $record->status);
        $this->assertSame('remote-contract-1', $record->remote_id);
        $this->assertNotNull($record->synced_at);

        $service->markFailed($record, 'Bearer raw-token client_secret=hidden-value');
        $this->assertSame('failed', $record->status);
        $this->assertStringNotContainsString('raw-token', $record->last_error);
        $this->assertStringNotContainsString('hidden-value', $record->last_error);
    }
}
