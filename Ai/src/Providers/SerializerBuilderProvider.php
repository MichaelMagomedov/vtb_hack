<?php

declare(strict_types=1);

namespace App\Ai\Providers;

use Illuminate\Support\ServiceProvider;
use JMS\Serializer\SerializerBuilder;

final class SerializerBuilderProvider extends ServiceProvider
{
    public function register(): void {
        /** @var SerializerBuilder $builder */
        $builder = $this->app->get(SerializerBuilder::class);
        $builder->addMetadataDir((string)config('ai.serializer.yml_path'), 'App\Ai');
    }
}
