<?php

declare(strict_types=1);

namespace App\Banking\Jobs;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Jobs\AfterAssistantRunJob\AfterAssistantRunJob;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Dto\UpdateLoadStatusDto;
use App\Ai\Services\Load\LoadService;
use App\Banking\Services\ParseTransactions\Dto\CreateTransactionsByParsedDataParamsDto;
use App\Banking\Services\ParseTransactions\TransactionsParseService;
use App\Root\Jobs\Middlewares\LoadWithoutOverlapping\LoadWithoutOverlappingMiddleware;
use Illuminate\Database\ConnectionInterface;
use Throwable;

class SaveParsedTransactionsDataJob extends AfterAssistantRunJob
{
    // из-за LoadWithoutOverlapping когда предыдущая загрузка в статусе need stoop
    // он пере отправляет задание в очередь и следовательно
    public $tries = 20;

    // после ошибки ждем 10 секунд после ошибки и пытаемся запустить заново
    // это нужно что бы предыдущий процесс успел завершится и мы заного попробовали
    public $backoff = 10;

    public function __construct(
        public string $loadId,
    )
    {
    }

    // транзакции в одном файле могут парсится и сохранятся паралельно
    // а вот весь процесс загрузки (совокупность параллельных процессов парсинга) идут друг за дружкой
    // что бы корректно срабатывали механизмы отката
    public function middleware()
    {
        return [new LoadWithoutOverlappingMiddleware($this->loadId, $this->backoff)];
    }

    public function handle(
        ConnectionInterface      $connection,
        LoadRepository           $loadRepository,
        TransactionsParseService $transactionsParseService,
    ): void
    {
        try {
            $connection->beginTransaction();
            $load = $loadRepository->findById($this->loadId);
            // если загрузка была остановлена или зафейлена то дальше не идем
            if ($load->getStatus()->isInterrupted()) {
                $connection->commit();
                return;
            }
            $transactionsParseService->createTransactionByParsedData(new CreateTransactionsByParsedDataParamsDto(
                $this->getParsedData(),
                $this->loadId
            ));
            $connection->commit();
        } catch (Throwable $exception) {
            // так как мы хотим что бы tries срабатывал только в рамках middleware
            // LoadWithoutOverlappingMiddleware то для всех остальных ошибок мы делаем логирование и
            // вызываем метода failed самостоятельно
            $connection->rollBack();
            report($exception);
            $this->failed($exception);
        }
    }

    public function failed(Throwable $exception)
    {
        /** @var LoadService $loadService */
        $loadService = app(LoadService::class);
        $loadService->updateStatus(new UpdateLoadStatusDto($this->loadId, LoadStatusEnum::FAIL, $exception));
    }
}
