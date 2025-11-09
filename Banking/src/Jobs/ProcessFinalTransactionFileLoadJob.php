<?php

declare(strict_types=1);

namespace App\Banking\Jobs;

use App\Ai\Entities\LoadEntity;
use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Jobs\AfterAssistantRunJob\AfterAssistantRunJob;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\LoadService;
use App\Banking\Events\TransactionChangeEvent;
use App\Banking\Repositories\Transaction\TransactionRepository;
use App\Banking\Services\ParseTransactions\TransactionsParseService;
use App\PersonalData\Services\Messenger\MessengerService;
use App\Recommendation\Jobs\StarGenerateAiExpensesTemplateJob;
use App\Recommendation\Services\UserExpensesTemplateAIGenerate\UserExpensesTemplateAIGenerateService;
use App\Root\Jobs\Middlewares\LoadWithoutOverlapping\LoadWithoutOverlappingMiddleware;
use Illuminate\Database\ConnectionInterface;
use Throwable;

class ProcessFinalTransactionFileLoadJob extends AfterAssistantRunJob
{
    // из-за LoadWithoutOverlapping когда предыдущая загрузка в статусе need stoop
    // он пере отправляет задание в очередь и следовательно
    public $tries = 30;

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
        LoadService              $loadService,
        TransactionsParseService $transactionsParseService,
    ): void
    {
        try {
            $connection->beginTransaction();
            $load = $loadRepository->findById($this->loadId);

            // выполняем действия до завершения
            // если загрузка была остановлена или зафейлена то дальше не идем
            if ($load->getStatus()->isInterrupted()) {
                $transactionsParseService->revertTransactionIfParseFail($this->loadId);
            }

            //завершаем
            $load = $loadService->finalize($load);

            // выполняем действие после завершения
            // если зафейлилось отправляем печальное сообщение
            if ($load->getStatus() === LoadStatusEnum::FAIL) {
                $this->afterLoadFailed($load);
            }
            // иначе хорошее сообщение
            if ($load->getStatus() === LoadStatusEnum::SUCCESS) {
                $this->afterLoadSuccess($load);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            // так как мы хотим что бы tries срабатывал только в рамках middleware
            // LoadWithoutOverlappingMiddleware то для всех остальных ошибок мы делаем логирование и
            $connection->rollBack();
            report($exception);
        }
    }

    private function afterLoadFailed(LoadEntity $load): void
    {
        // dependency inject
        /** @var MessengerService $messengerService */
        $messengerService = app(MessengerService::class);

        $message = $load->getReason() ?? trans('banking::transaction.job_exceptions.save_account_data_unknown_exception');
        $messengerService->sendByLoadId($load->getId(), $message);
    }


    private function afterLoadSuccess(LoadEntity $load): void
    {
        // dependency inject
        /** @var MessengerService $messengerService */
        $messengerService = app(MessengerService::class);
        $this->dispatchTransactionChangeEvent($load);
        $message = trans('banking::transaction.success_parsed');
        $messengerService->sendByLoadId($load->getId(), $message, true);

        // запускаем генерацию шаблонов от AI
        dispatch(new StarGenerateAiExpensesTemplateJob($load->getUserId()));
    }

    // вызываем пересчет графиков
    private function dispatchTransactionChangeEvent(LoadEntity $load): void
    {
        /** @var $transactionRepository TransactionRepository */
        $transactionRepository = app(TransactionRepository::class);
        $lastTransaction = $transactionRepository->findLastTransaction($load->getUserId());
        event(new TransactionChangeEvent($lastTransaction));
    }

}

