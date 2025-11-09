<?php

use App\Ai\UserInterface\Channels\LoadChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::namespace('Load')
    ->middleware('auth:api')
    ->prefix('load')
    ->group(static function () {

    });

// user Id так как подписываемся на все load по пользователю
Broadcast::channel('load.{userId}', LoadChannel::class);
