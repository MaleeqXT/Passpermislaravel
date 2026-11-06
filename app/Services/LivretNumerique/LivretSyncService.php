<?php

namespace App\Services\LivretNumerique;

use App\Exceptions\RdvPermisApiException;
use App\Models\LivretLesson;
use App\Models\LivretSyncLog;
use App\Models\LivretTrainingContract;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class LivretSyncService
{
    public function attempt(Model $record, string $operation): void
    {
        $record->forceFill(['sync_status' => 'sending', 'sync_attempts' => ((int) $record->sync_attempts) + 1, 'last_sync_attempt_at' => now(), 'provider_error_code' => null, 'provider_error_message' => null])->save();
        $this->log($record, $operation, 'sending');
    }

    public function synced(Model $record, string $operation, ?string $providerId): void
    {
        $field = $record instanceof LivretLesson ? 'provider_lesson_id' : 'provider_contract_id';
        $record->forceFill([$field => $providerId ?: $record->{$field}, 'sync_status' => 'synced', 'synced_at' => now(), 'provider_error_code' => null, 'provider_error_message' => null])->save();
        $this->log($record, $operation, 'synced');
    }

    public function failed(Model $record, string $operation, Throwable $error): void
    {
        $unverified = $error instanceof ProviderSchemaUnavailable;
        $retryable = $error instanceof RdvPermisApiException && ($error->rdvPermisStatus === 429 || $error->rdvPermisStatus >= 500);
        $status = $retryable ? 'pending' : ($unverified ? 'needs_resync' : 'failed');
        $code = $unverified ? 'UNVERIFIED_PROVIDER_SCHEMA' : ($error instanceof RdvPermisApiException ? $error->rdvPermisError : 'LIVRET_SYNC_FAILED');
        $record->forceFill(['sync_status' => $status, 'provider_error_code' => $code, 'provider_error_message' => $this->safe($error->getMessage())])->save();
        $this->log($record, $operation, $status, $error instanceof RdvPermisApiException ? $error->rdvPermisStatus : null, $code);
    }

    public function canRetry(Model $record): bool { return in_array($record->sync_status, ['pending', 'failed', 'needs_resync'], true); }

    private function log(Model $record, string $operation, string $result, ?int $status = null, ?string $code = null): void
    {
        LivretSyncLog::create(['resource_type' => $record instanceof LivretLesson ? 'lesson' : 'contract', 'resource_id' => $record->getKey(), 'provider_resource_id' => $record->provider_lesson_id ?? $record->provider_contract_id, 'operation' => $operation, 'result' => $result, 'http_status' => $status, 'provider_error_code' => $code, 'attempt' => (int) $record->sync_attempts]);
    }

    private function safe(string $message): string
    {
        $message = preg_replace('/(bearer\s+|token|secret|password)\S+/i', '$1[redacted]', $message) ?? 'Livret synchronization failed.';
        return mb_substr($message, 0, 1000);
    }
}
