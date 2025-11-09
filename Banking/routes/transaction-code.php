<?php

use Illuminate\Support\Facades\Route;

Route::namespace('TransactionCode')
    ->prefix('transaction-code')
    ->middleware('auth:api')
    ->group(static function () {
        Route::get('/', 'TransactionCodeController@list');
    });
