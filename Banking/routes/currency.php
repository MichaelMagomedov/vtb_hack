<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Currency')
    ->middleware('auth:api')
    ->prefix('currency')->group(static function () {
        Route::get('/', 'CurrencyController@list');
    });
