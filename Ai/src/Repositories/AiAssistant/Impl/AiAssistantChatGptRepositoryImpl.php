<?php

declare(strict_types=1);

namespace App\Ai\Repositories\AiAssistant\Impl;

use App\Ai\Repositories\AiAssistant\AiAssistantRepository;
use App\Ai\Enums\ChatGptRunStatusEnum;
use App\Ai\Clients\ChatGptClient;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use Throwable;

final class AiAssistantChatGptRepositoryImpl implements AiAssistantRepository
{
    // делаем 3 попытки а потом ретраем еще раз попытку уже на
    // уровне job см ProcessAssistantRunJob но с задержкой в 5-10 секунд что бы
    // в retry не используем usleep так как он блочит процесс (выбран верный метод через перезапуск джобы с задержкой)
    private const MAX_AI_REQUEST_ATTEMPT = 3;
    private string $vectorStoreId;

    public function __construct(
        protected readonly ChatGptClient         $client,
        #[Storage('local')] protected Filesystem $filesystem
    ) {
        $this->vectorStoreId = config('ai.chat_gpt.vector_store_id');
    }

    public function sendMessageToAssistant(string $threadId, string $prompt): void {
        // иногда поиск проходит не гладко и возвращается какая-то херня и если его переспросить то норм
        $this->client->getRawClient()->threads()->messages()->create($threadId, [
            'role' => 'user',
            'content' => $prompt,
        ]);
    }

    public function uploadFileToStore(string $filePath, string $fileName): void {
        // находим и удаляем файлы стора, который закреплен за асистентом
        $openAiClient = $this->client->getRawClient();
        $vectorStoreFiles = $openAiClient->vectorStores()->files()->list($this->vectorStoreId);
        foreach ($vectorStoreFiles['data'] as $fileData) {
            try {
                $file = $openAiClient->files()->retrieve($fileData['id']);
                if ($file['filename'] === $fileName) {
                    $openAiClient->files()->delete($file['id']);
                }
            } catch (Throwable $exception) {
                if (!str_contains(mb_strtolower($exception->getMessage()), mb_strtolower('no such file'))) {
                    throw $exception;
                }
            }

        }
        // загружаем новый
        $fileResponse = $openAiClient->files()->upload([
            'purpose' => 'assistants',
            'file' => $this->filesystem->readStream($filePath)
        ]);
        // добавляем его в индекс
        $openAiClient->vectorStores()->files()->create($this->vectorStoreId, ['file_id' => $fileResponse['id']]);
    }

    public function getAssistantRunInfo(string $threadId, string $runId): ThreadRunResponse {
        $openAiClient = $this->client->getRawClient();
        return retry(self::MAX_AI_REQUEST_ATTEMPT, static function () use ($threadId, $runId, $openAiClient) {
            return $openAiClient->threads()->runs()->retrieve($threadId, $runId);
        });
    }

    public function createAssistantRun(string $threadId, array $params): ThreadRunResponse {
        $openAiClient = $this->client->getRawClient();
        return retry(self::MAX_AI_REQUEST_ATTEMPT, static function () use ($threadId, $openAiClient, $params) {
            $run = $openAiClient->threads()->runs()->create($threadId, $params);
            return $run;
        });
    }

    public function stopAssistantActiveRun(string $threadId): void {
        $openAiClient = $this->client->getRawClient();
        retry(self::MAX_AI_REQUEST_ATTEMPT, static function () use ($threadId, $openAiClient) {
            foreach ($openAiClient->threads()->runs()->list($threadId)->data as $run) {
                if (in_array($run->status, array_column(ChatGptRunStatusEnum::inProcess(), 'value'))) {
                    $openAiClient->threads()->runs()->cancel($threadId, $run->id);
                }
            }
        });
    }

}

