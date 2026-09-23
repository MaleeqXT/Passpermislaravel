<?php

namespace App\Console\Commands;

use App\Models\RdvPermisToken;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class ImportRdvPermisAccessToken extends Command
{
    protected $signature = 'rdvpermis:import-access-token
        {user_id : Existing Laravel user who will call the local RDVPermis API}
        {--expires-in=300 : Remaining access-token lifetime in seconds (1-86400)}';

    protected $description = 'LOCAL ONLY: import a temporary Swagger access token using a hidden prompt';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('This command is available only in the local environment.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('An interactive terminal with hidden input is required.');

            return self::FAILURE;
        }

        $expiresIn = filter_var($this->option('expires-in'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 86400],
        ]);
        if ($expiresIn === false) {
            $this->error('The --expires-in option must be an integer between 1 and 86400 seconds.');

            return self::FAILURE;
        }

        try {
            $user = User::query()->withoutEagerLoads()->whereNull('deleted_at')
                ->find($this->argument('user_id'), ['id']);
            if (! $user) {
                $this->error('Laravel user not found.');

                return self::FAILURE;
            }

            // Never fall back to visible input if the terminal cannot hide it.
            $accessToken = $this->secret('RDVPermis access token (hidden; paste without Bearer)', false);
            if (! is_string($accessToken) || $accessToken === ''
                || preg_match('/[\x00-\x20\x7f]/', $accessToken)) {
                $this->error('Enter a non-empty access token without spaces, line breaks or the Bearer prefix.');

                return self::FAILURE;
            }

            $expiresAt = now()->addSeconds($expiresIn);

            // Save through the model so its existing encrypted casts remain in effect.
            // An imported token has no corresponding refresh token or verified scope metadata.
            RdvPermisToken::query()->updateOrCreate(['user_id' => $user->getKey()], [
                'access_token' => $accessToken,
                'access_token_expires_at' => $expiresAt,
                'refresh_token' => null,
                'refresh_expires_at' => null,
                'scopes' => null,
                'status' => 'connected',
                'last_error' => null,
            ]);
        } catch (Throwable) {
            // Do not report the exception: console/DB exceptions may contain sensitive input.
            $this->error('Token import failed. Check hidden-input support and the local database/encryption configuration.');

            return self::FAILURE;
        } finally {
            unset($accessToken);
        }

        $this->info('RDVPermis access token stored encrypted for user '.$user->getKey().'.');
        $this->line('Expires at '.$expiresAt->toIso8601String().'. Import a fresh token after expiry.');

        return self::SUCCESS;
    }
}
