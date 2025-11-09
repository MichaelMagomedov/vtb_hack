<?php

use Illuminate\Support\Facades\Route;

Route::namespace('TransactionParse')
    ->prefix('transaction-parse')
    ->group(static function () {

        // на этот роут стучится puzzlebot и мы его аутентифицируем как клиента
        Route::middleware('auth.client')->group(static function () {
            Route::get('/', 'TransactionParseController@start');
        });

        // этот роут мы дергаем сами внутри приложения
        Route::middleware('auth:api')->group(static function () {
            Route::post('/parse-html', 'TransactionParseController@parseHtml');
        });
    });
