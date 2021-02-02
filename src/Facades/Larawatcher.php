<?php

namespace Larawatcher\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static tag($tag)
 * @method static untag($tag)
 * @method static getType(): string
 */
class Larawatcher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Larawatcher\Larawatcher::class;
    }
}
