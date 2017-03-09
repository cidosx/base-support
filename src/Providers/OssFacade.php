<?php

namespace Providers;

use Illuminate\Support\Facades\Facade;

class OssFacade extends Facade {

    protected static function getFacadeAccessor() {
        return 'oss';
    }
}
