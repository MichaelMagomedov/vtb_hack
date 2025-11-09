<?php

declare(strict_types=1);

namespace App\Ai\Repositories\AiAssistant;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;

/**
 * Отдельный репозиторий на работу с внутрянкой асистентов
 */
interface AiAssistantRepository
{
    public function sendMessageToAssistant(string $threadId, string $prompt): void;

    public function createAssistantRun(string $threadId, array $params): ThreadRunResponse;

    public function getAssistantRunInfo(string $threadId, string $runId): ThreadRunResponse;

    public function stopAssistantActiveRun(string $threadId): void;

    public function uploadFileToStore(string $filePath, string $fileName): void;
}

