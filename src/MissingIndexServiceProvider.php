<?php

namespace OeleDev\MissingIndex;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class MissingIndexServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/missing-index.php',
            'missing-index'
        );

        $this->app->singleton(MissingIndexDetector::class, function ($app) {
            return new MissingIndexDetector();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/missing-index.php' => config_path('missing-index.php'),
            ], 'missing-index-config');
        }

        // Push middleware to the end of the global middleware stack
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(MissingIndexMiddleware::class);
    }
}
