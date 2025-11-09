<?php

use Illuminate\Support\Facades\Route;

Route::namespace('AccountBalance')
    ->prefix('account-balance')
    ->middleware('auth:api')
    ->group(static function () {
        Route::get('/', 'AccountBalanceController@find');
        Route::get('/list', 'AccountBalanceController@list');
        Route::post('/', 'AccountBalanceController@create');
        Route::get('/{id}', 'AccountBalanceController@get');
        Route::put('/order', 'AccountBalanceController@updateOrder');
        Route::put('/{id}', 'AccountBalanceController@update');
        Route::delete('/{id}', 'AccountBalanceController@delete');
    });
