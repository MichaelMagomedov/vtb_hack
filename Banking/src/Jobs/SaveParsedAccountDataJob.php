<?php

declare(strict_types=1);

namespace App\Banking\Jobs;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Jobs\AfterAssistantRunJob\AfterAssistantRunJob;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Dto\UpdateLoadStatusDto;
use App\Ai\Services\Load\LoadService;
use App\Banking\Services\ParseTransactions\Dto\CreateAccountByParsedDataParamsDto;
use App\Banking\Services\ParseTransactions\TransactionsFileParseService;
use Illuminate\Database\ConnectionInterface;
use Throwable;

class SaveParsedAccountDataJob extends AfterAssistantRunJob
{
    public function __construct(
        public string $loadId,
        public string $pdfPath,
    ) {
    }

    public function handle(
        ConnectionInterface          $connection,
        LoadRepository               $loadRepository,
        TransactionsFileParseService $transactionsFileParseService,
    ): void {
        try {
            $connection->beginTransaction();
            $load = $loadRepository->findById($this->loadId);
            // если загрузка была остановлена или зафейлена то дальше не идем
            if ($load->getStatus()->isInterrupted()) {
                $connection->commit();
                return;
            }
            $transactionsFileParseService->createAccountByParsedData(new CreateAccountByParsedDataParamsDto(
                $this->getParsedData(),
                $this->loadId,
                $this->pdfPath
            ));
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw  $exception;
        }
    }

    public function failed(Throwable $exception) {
        /** @var LoadService $loadService */
        $loadService = app(LoadService::class);
        $loadService->updateStatus(new UpdateLoadStatusDto($this->loadId, LoadStatusEnum::FAIL, $exception));
    }
}
