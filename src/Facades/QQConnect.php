<?php

namespace Superzc\QQConnect\Facades;

use Illuminate\Support\Facades\Facade;

class QQConnect extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'qqconnect';
    }
}
