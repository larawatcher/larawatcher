<?php

namespace Larawatcher\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class FakeClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Http::fake();
    }
}
