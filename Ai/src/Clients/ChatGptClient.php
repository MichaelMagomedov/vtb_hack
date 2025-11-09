<?php

declare(strict_types=1);

namespace App\Ai\Clients;

use App\Ai\Entities\LoadEntity;
use App\Ai\Jobs\AfterAssistantRunJob\AfterAssistantRunnable;
use App\Ai\Enums\ChatGptApiEnum;
use App\Ai\Enums\ChatGptModelTypeEnum;
use App\Ai\Jobs\ProcessAssistantPromptJob;
use App\Ai\Jobs\ProcessAssistantRunJob;
use App\Ai\Repositories\Ai\Dto\AssistantPromptInstructionDto;
use App\Root\Factories\GuzzleProxyClientFactory;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Support\Facades\Bus;
use OpenAI;
use OpenAI\Client;
use Symfony\Component\HttpFoundation\Response;

final class ChatGptClient
{
    private Client $client;

    private const MAX_AI_REQUEST_ATTEMPT = 3;

    private string $assistantId;

    private string $vectorStoreId;

    private ChatGptModelTypeEnum $modelType;

    public function __construct(
        protected GuzzleProxyClientFactory $proxyClientFactory,
    ) {
        $this->assistantId = config('ai.chat_gpt.assistant_id');
        $this->vectorStoreId = config('ai.chat_gpt.vector_store_id');
        $this->modelType = ChatGptModelTypeEnum::from(config('ai.chat_gpt.model'));
        $this->client = OpenAI::factory()
            ->withApiKey(config('ai.chat_gpt.key'))
            ->withHttpClient($proxyClientFactory->create(
                ChatGptApiEnum::V1->value,
                Response::HTTP_NOT_FOUND
            ))
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();
    }

    public function getRawClient(): Client {
        return $this->client;
    }

    /**
     * @deprecated
     * ВАЖНО: если нам не нужно использовать vectorStore
     */
    public function getCompletionJsonResponse(
        string $prompt,
        array  $properties
    ): array {
        $client = $this->client;
        $modelType = $this->modelType;
        return retry(self::MAX_AI_REQUEST_ATTEMPT, static function () use ($prompt, $client, $properties, $modelType) {
            $response = $client->chat()->create([
                'model' => $modelType->value,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'tools' => [
                    [
                        'type' => 'function',
                        'function' => [
                            'name' => 'simple_function',
                            'parameters' => $properties,
                        ],
                    ],
                ]
            ]);

            return json_decode($response->choices[0]->message->toolCalls[0]->function->arguments, true);
        });
    }

    /**
     * ВАЖНО: если нам нужен использовать vectorStore и контекст асистента
     *
     * @param AssistantPromptInstructionDto[] $instructions - набор инструкций которые должен выполнить чат бот
     * @param array $returnSchema - какой формат должна вернуть нейронка в итоге
     * @param LoadEntity $load -  нужен что бы в ProcessAssistantRunJob можно было обновлять статус загрузки и передавать результат между джобами
     * @param AfterAssistantRunnable $afterJob - джоба которая будет выполнена после того как получеы данные
     *
     * Все это делается через job так как chatgpt 1 run может выполнять несколько секунд и каждый раз нам нужно
     * заного класть задание в очередь если run не успел выполниться
     */
    public function getAssistantJsonResponse(
        array                  $instructions,
        array                  $returnSchema,
        LoadEntity             $load,
        AfterAssistantRunnable $afterJob
    ): PendingChain {
        $client = $this->client;
        $vectorStoreId = $this->vectorStoreId;
        $modelType = $this->modelType;
        return retry(self::MAX_AI_REQUEST_ATTEMPT, function () use ($instructions, $load, $client, $vectorStoreId, $returnSchema, $afterJob, $modelType) {
            // первым шагом отправляем сообщение
            $thread = $client->threads()->create([]);
            // даем последнему шагу информацию о чате из которого брать информацию
            $afterJob->setThreadId($thread->id);

            // формируем цепочку заданий по обработке инструкций
            $chain = [];
            foreach ($instructions as $instruction) {
                $chain[] = new ProcessAssistantPromptJob($load->getId(), $instruction->getPrompt(), $thread->id);
                // если нам требуется поиск vector store
                if ($instruction->getUseFileSearch()) {
                    $chain[] = new ProcessAssistantRunJob(
                        $thread->id,
                        $load->getId(),
                        [
                            'model' => $modelType->value,
                            'assistant_id' => $this->assistantId,
                            'tool_resources' => [
                                'file_search' => [
                                    'vector_store_ids' => [$vectorStoreId],
                                ],
                            ],
                            'tool_choice' => [
                                "type" => "file_search"
                            ],
                        ]
                    );
                }
            }
            // получаем результат в нужном нам формате
            $chain[] = new ProcessAssistantRunJob(
                $thread->id,
                $load->getId(),
                [
                    'model' => $modelType->value,
                    'assistant_id' => $this->assistantId,
                    'tools' => [
                        [
                            'type' => 'function',
                            'function' => [
                                'name' => 'simple_function',
                                'description' => 'Получение ответа в нужном формате',
                                'parameters' => $returnSchema,
                            ],
                        ],
                    ]
                ]
            );
            // продолжаем выполнение нужного нам действия
            $chain[] = $afterJob;

            // все это разбито на job так как асистент может работать очень долго (1-5 минут)
            return Bus::chain($chain);
        });
    }
}
