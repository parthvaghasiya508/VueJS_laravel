<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use \App\Services\RoomDB\RoomDBService as RoomDBService;

class SupplierRoomDbProvider extends ServiceProvider {

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind('roomdb', function () {
            return new RoomDBService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        
    }

}
