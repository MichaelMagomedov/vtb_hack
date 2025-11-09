<?php

declare(strict_types=1);

namespace App\Banking\Providers;

use Illuminate\Support\ServiceProvider;
use JMS\Serializer\SerializerBuilder;

final class SerializerBuilderProvider extends ServiceProvider
{
    public function register(): void {
        /** @var SerializerBuilder $builder */
        $builder = $this->app->get(SerializerBuilder::class);
        $builder->addMetadataDir((string)config('banking.serializer.yml_path'), 'App\Banking');
    }
}
