<?php

use Illuminate\Support\Facades\Route;

Route::namespace('TransactionCategory')
    ->prefix('transaction-category')
    ->middleware('auth:api')
    ->group(static function () {
        Route::get('/', 'TransactionCategoryController@list');
        Route::get('/user', 'TransactionCategoryController@byUser');
    });
