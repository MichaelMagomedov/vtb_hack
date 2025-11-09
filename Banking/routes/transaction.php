<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Transaction')
    ->prefix('transaction')
    ->group(static function () {

        // на этот роут стучится puzzlebot и мы его аутентифицируем как клиента
        Route::middleware('auth.client')->group(static function () {
            Route::post('/parse', 'TransactionController@parse');
        });

        Route::middleware('auth:api')->group(static function () {
            Route::get('/', 'TransactionController@list');
            Route::get('/destination', 'TransactionController@destinationAutocomplete');
            Route::post('/', 'TransactionController@create');
            Route::post('/verify', 'TransactionController@verify');
            Route::get('/{id}', 'TransactionController@get');
            Route::put('/order', 'TransactionController@updateOrder');
            Route::put('/{id}', 'TransactionController@update');
            Route::delete('/{id}', 'TransactionController@delete');
        });
    });
