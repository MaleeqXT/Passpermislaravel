<?php

namespace App\Console;

use Illuminate\Console\Application as BaseApplication;
use Illuminate\Console\Command;
use Illuminate\Console\ContainerCommandLoader;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ArtisanApplication extends BaseApplication
{
    /**
     * Create a new Artisan console application instance.
     */
    public function __construct(Container $laravel, Dispatcher $events, $version)
    {
        parent::__construct($laravel, $events, $version);
    }

    /**
     * Set the container command loader for lazy resolution.
     */
    public function setContainerCommandLoader(): static
    {
        $this->setCommandLoader(new class($this->laravel, $this->commandMap) extends ContainerCommandLoader
        {
            public function get(string $name): SymfonyCommand
            {
                $command = parent::get($name);

                if ($command instanceof Command) {
                    $command->setLaravel($this->container);
                }

                return $command;
            }
        });

        return $this;
    }
}
