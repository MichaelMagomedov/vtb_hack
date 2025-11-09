<?php

declare(strict_types=1);

namespace App\Ai\Providers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;

final class TranslationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lang::addNamespace('ai', app_path('Ai/resources/lang'));
    }
}
