<?php

declare(strict_types=1);

namespace App\Banking\Providers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;

final class TranslationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lang::addNamespace('banking', app_path('Banking/resources/lang'));
    }
}
