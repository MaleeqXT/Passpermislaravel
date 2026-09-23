<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Tests\TestCase;

class ConsoleCommandContainerTest extends TestCase
{
    public function test_key_generate_command_receives_the_laravel_container(): void
    {
        $command = $this->app->make(KeyGenerateCommand::class);

        $this->assertNotNull($command->getLaravel());
        $this->assertSame($this->app, $command->getLaravel());
    }

    public function test_artisan_can_run_key_generate_help_without_error(): void
    {
        $kernel = $this->app->make(ConsoleKernelContract::class);

        $status = $kernel->call('key:generate', ['--help' => true]);

        $this->assertSame(0, $status);
    }
}
