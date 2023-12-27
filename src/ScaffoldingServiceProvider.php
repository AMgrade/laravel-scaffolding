<?php

declare(strict_types=1);

namespace AMgrade\Scaffolding;

use Illuminate\Support\ServiceProvider;

class ScaffoldingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scaffolding.php', 'scaffolding');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\MakeConsoleCommand::class,
                Console\Commands\MakeObserverCommand::class,
                Console\Commands\MakeRepositoryCommand::class,
            ]);
        }
    }
}
