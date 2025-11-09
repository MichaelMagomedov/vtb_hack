<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Account')
    ->middleware('auth:api')
    ->prefix('account')->group(static function () {
        Route::get('/', 'AccountController@list');
        Route::get('/{id}', 'AccountController@get');
        Route::put('/order', 'AccountController@updateOrder');
        Route::put('/{id}', 'AccountController@update');
        Route::delete('/{id}', 'AccountController@delete');
    });
