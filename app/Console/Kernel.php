<?php

namespace App\Console;

use App\Console\Commands\ImportStudents;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Create a new console kernel instance.
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        parent::__construct($app, $events);

        $this->app->alias(ConsoleKernelContract::class, self::class);
    }

    /**
     * Define the application's command schedule.
     */

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:work --tries=3 --backoff=120 --sleep=5 --stop-when-empty')->everyMinute();
    }

    protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            $this->artisan = (new ArtisanApplication($this->app, $this->events, $this->app->version()))
                ->resolveCommands($this->commands)
                ->setContainerCommandLoader();

            if ($this->symfonyDispatcher instanceof \Symfony\Contracts\EventDispatcher\EventDispatcherInterface) {
                $this->artisan->setDispatcher($this->symfonyDispatcher);
                $this->artisan->setSignalsToDispatchEvent();
            }
        }

        return $this->artisan;
    }
}
