<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Bank')
    ->middleware('auth:api')
    ->prefix('bank')
    ->group(static function () {
        Route::get('/', 'BankController@list');
    });
