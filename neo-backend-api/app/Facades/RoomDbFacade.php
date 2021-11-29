<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class RoomDbFacade extends Facade {

    protected static function getFacadeAccessor() {
        return 'roomdb';
    }
    
}