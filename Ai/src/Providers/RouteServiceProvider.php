<?php

declare(strict_types=1);

namespace App\Ai\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    public function map() {
        Route::prefix('api/v1/ai')
            ->middleware('api')
            ->namespace('App\Ai\UserInterface\Controllers')
            ->group(function () {
                require dirname(__DIR__, 2) . '/routes/load.php';
            });
    }
}
