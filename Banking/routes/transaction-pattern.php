<?php

use Illuminate\Support\Facades\Route;

Route::namespace('UserTransactionPattern')
    ->prefix('transaction-pattern')
    ->middleware('auth:api')
    ->group(static function () {
        Route::get('/', 'UserTransactionPatternController@get');
    });
