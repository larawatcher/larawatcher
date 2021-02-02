<?php

namespace Larawatcher\Http\Middleware;

use Closure;
use Larawatcher\Actions\Save;

class LarawatcherMiddleware
{
    private Save $saveAction;

    public function __construct(Save $saveAction)
    {
        $this->saveAction = $saveAction;
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $this->saveAction->handle();
    }
}
