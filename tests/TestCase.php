<?php

namespace Larawatcher\Tests;

use Larawatcher\Providers\FakeClientServiceProvider;
use Larawatcher\Providers\LarawatcherServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [FakeClientServiceProvider::class, LarawatcherServiceProvider::class];
    }
}
