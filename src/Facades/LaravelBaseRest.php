<?php

namespace KhanCode\LaravelBaseRest\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelBaseRest extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelbaserest';
    }
}
