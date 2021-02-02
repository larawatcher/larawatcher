<?php

namespace Larawatcher\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Larawatcher\Http\Middleware\LarawatcherMiddleware;
use Larawatcher\Larawatcher;

class LarawatcherServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../../config/larawatcher.php' => config_path('larawatcher.php'),
                ],
                'config',
            );
        }

        foreach (config('larawatcher.middleware_groups') as $group) {
            $this->app['router']->pushMiddlewareToGroup($group, LarawatcherMiddleware::class);
        }

        $this->app->get(Larawatcher::class)->handle();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/larawatcher.php', 'larawatcher');
        $this->app->singleton(LarawatcherMiddleware::class);
        $this->app->singleton(Larawatcher::class);
        $this->app->singleton(
            'larawatcher.client',
            fn () => Http::baseUrl(
                sprintf('http://%s:%s', config('larawatcher.endpoint'), config('larawatcher.port')),
            )->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]),
        );
    }
}
