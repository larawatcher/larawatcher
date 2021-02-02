<?php

namespace Larawatcher\Actions;

use Illuminate\Http\Client\PendingRequest;

abstract class BaseAction
{
    protected PendingRequest $client;

    public function __construct()
    {
        $this->client = resolve('larawatcher.client');
    }

    abstract public function handle();
}
