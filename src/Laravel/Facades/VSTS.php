<?php

namespace Jeylabs\VSTS\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class VSTS extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'vsts';
    }
}
