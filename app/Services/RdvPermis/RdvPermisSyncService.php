<?php

namespace App\Services\RdvPermis;

use App\Models\RdvPermisSyncRecord;
use Throwable;

/**
 * Maintains local synchronization state without storing provider credentials
 * or raw provider responses.
 */
class RdvPermisSyncService
{
    public function recordFor(string $entityType, string|int $entityId): RdvPermisSyncRecord
    {
        return RdvPermisSyncRecord::query()->firstOrCreate(
            ['entity_type' => $entityType, 'entity_id' => (string) $entityId],
            ['status' => 'pending'],
        );
    }

    public function markAttempt(RdvPermisSyncRecord $record): RdvPermisSyncRecord
    {
        $record->forceFill([
            'status' => 'pending',
            'last_sync_attempt_at' => now(),
            'sync_attempts' => ((int) $record->sync_attempts) + 1,
            'last_error' => null,
        ])->save();

        return $record;
    }

    public function markSynced(RdvPermisSyncRecord $record, ?string $remoteId = null): RdvPermisSyncRecord
    {
        $record->forceFill([
            'status' => 'synced',
            'remote_id' => $remoteId ?? $record->remote_id,
            'synced_at' => now(),
            'last_error' => null,
        ])->save();

        return $record;
    }

    public function markFailed(RdvPermisSyncRecord $record, Throwable|string $error): RdvPermisSyncRecord
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        $record->forceFill([
            'status' => 'failed',
            'last_error' => $this->sanitizeError($message),
        ])->save();

        return $record;
    }

    private function sanitizeError(string $message): string
    {
        $sanitized = preg_replace([
            '/Bearer\s+[^\s]+/i',
            '/(access_token|refresh_token|client_secret|authorization_code|code)=?[^\s,&]+/i',
        ], [
            'Bearer [redacted]',
            '$1=[redacted]',
        ], $message) ?? 'RdvPermis synchronization failed.';

        return mb_substr($sanitized, 0, 1000);
    }
}
