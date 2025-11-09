<?php

declare(strict_types=1);

namespace App\Ai\Providers;

use App\Ai\Repositories\AiAssistant\AiAssistantRepository;
use App\Ai\Repositories\AiAssistant\Impl\AiAssistantChatGptRepositoryImpl;
use App\Ai\Repositories\AiPrompt\AiPromptRepository;
use App\Ai\Clients\ChatGptClient;
use App\Ai\Repositories\AiPrompt\Impl\AiPromptChatGptRepositoryImpl;
use App\Ai\Repositories\AiThreadEntity\AiThreadEntityRepository;
use App\Ai\Repositories\AiThreadEntity\Impl\AiThreadEntityDatabaseRepositoryImpl;
use App\Ai\Repositories\Load\Impl\LoadDatabaseRepositoryImpl;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Impl\LoadServiceImpl;
use App\Ai\Services\Load\LoadService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AiContextProvider extends ServiceProvider
{
    private $configs = [
        'ai.serializer' => 'serializer',
        'ai.chat_gpt' => 'chat_gpt',
    ];

    private function registerConfigs(): void {
        foreach ($this->configs as $key => $file) {
            $this->mergeConfigFrom(
                dirname(__DIR__, 2) . '/configs/' . $file . '.php',
                $key
            );
        }
    }

    private function registerServiceProvider(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(TranslationServiceProvider::class);
        $this->app->register(SerializerBuilderProvider::class);
    }

    private function registerServices(): void {
        $this->app->bind(LoadService::class, LoadServiceImpl::class);
        $this->app->bind(ChatGptClient::class, ChatGptClient::class);
    }

    private function registerRepositories(): void {
        $this->app->bind(LoadRepository::class, LoadDatabaseRepositoryImpl::class);
        $this->app->bind(AiPromptRepository::class, AiPromptChatGptRepositoryImpl::class);
        $this->app->bind(AiAssistantRepository::class, AiAssistantChatGptRepositoryImpl::class);
        $this->app->bind(AiThreadEntityRepository::class, AiThreadEntityDatabaseRepositoryImpl::class);
    }

    public function register(): void {
        $this->registerConfigs();
        $this->registerServices();
        $this->registerRepositories();
        $this->registerServiceProvider();
    }
}
