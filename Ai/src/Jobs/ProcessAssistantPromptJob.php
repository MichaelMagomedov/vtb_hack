<?php

declare(strict_types=1);

namespace App\Ai\Jobs;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Repositories\AiAssistant\AiAssistantRepository;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Dto\UpdateLoadStatusDto;
use App\Ai\Services\Load\LoadService;
use App\Banking\Jobs\ProcessFinalTransactionFileLoadJob;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class ProcessAssistantPromptJob implements ShouldQueue
{
    use Queueable, Batchable, Dispatchable, InteractsWithQueue;

    public $tries = 2;

    public function __construct(
        public string $loadId,
        public string $prompt,
        public string $threadId,
    ) {
    }

    public function handle(
        AiAssistantRepository $aiAssistantRepository,
        LoadRepository        $loadRepository
    ): void {
        $load = $loadRepository->findById($this->loadId);
        // если загрузка была остановлена или зафейлена то дальше не идем
        if ($load->getStatus()->isInterrupted()) {
            return;
        }

        $loadRepository->addInputCharsCount($this->loadId, strlen(str_replace(' ', '', $this->prompt)));
        $loadRepository->addInputWordsCount($this->loadId, str_word_count($this->prompt));
        $aiAssistantRepository->sendMessageToAssistant($this->threadId, $this->prompt);
    }

    public function failed(Throwable $exception) {
        /** @var LoadService $loadService */
        $loadService = app(LoadService::class);
        $loadService->updateStatus(new UpdateLoadStatusDto($this->loadId, LoadStatusEnum::FAIL, $exception));
        dispatch(new ProcessFinalTransactionFileLoadJob($this->loadId));
    }
}
