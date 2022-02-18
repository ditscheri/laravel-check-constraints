<?php

namespace Ditscheri\CheckConstraints\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ditscheri\CheckConstraints\CheckConstraints
 */
class CheckConstraints extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-check-constraints';
    }
}
