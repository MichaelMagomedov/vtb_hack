<?php

declare(strict_types=1);

namespace App\Ai\Jobs\AfterAssistantRunJob;


use App\Ai\Repositories\AiThreadEntity\AiThreadEntityRepository;
use App\Banking\Services\ParseTransactions\Exceptions\ParseAccountUnknownException;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

abstract class AfterAssistantRunJob implements AfterAssistantRunnable
{
    use Queueable, Batchable, Dispatchable, InteractsWithQueue;

    public string $threadId;

    public function setThreadId(string $threadId) {
        $this->threadId = $threadId;
    }

    protected function getParsedData(): array {
        $aiThreadRepository = app(AiThreadEntityRepository::class);
        $aiThread = $aiThreadRepository->findByThreadId($this->threadId);
        if ($aiThread === null) {
            throw new ParseAccountUnknownException();
        }

        return json_decode($aiThread->getFunctionCallParams() ?? '[]', true);
    }

}
