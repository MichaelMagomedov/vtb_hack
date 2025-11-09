<?php

declare(strict_types=1);

namespace App\Banking\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    public function map() {
        Route::prefix('api/v1/banking')
            ->middleware('api')
            ->namespace('App\Banking\UserInterface\Controllers')
            ->group(function () {
                require dirname(__DIR__, 2) . '/routes/transaction.php';
                require dirname(__DIR__, 2) . '/routes/transaction-parse.php';
                require dirname(__DIR__, 2) . '/routes/transaction-category.php';
                require dirname(__DIR__, 2) . '/routes/transaction-code.php';
                require dirname(__DIR__, 2) . '/routes/transaction-pattern.php';
                require dirname(__DIR__, 2) . '/routes/account.php';
                require dirname(__DIR__, 2) . '/routes/currency.php';
                require dirname(__DIR__, 2) . '/routes/bank.php';
                require dirname(__DIR__, 2) . '/routes/account-balance.php';
            });
    }
}
