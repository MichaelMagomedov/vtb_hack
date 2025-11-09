<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Impl;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Enums\LoadTypeEnum;
use App\Ai\Repositories\AiPrompt\AiPromptRepository;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Dto\CreateLoadDto;
use App\Ai\Services\Load\Dto\UpdateLoadStatusDto;
use App\Ai\Services\Load\LoadService;
use App\Banking\Jobs\ProcessFinalTransactionFileLoadJob;
use App\Banking\Jobs\SaveParsedTransactionsDataJob;
use App\Banking\Repositories\Account\AccountRepository;
use App\Banking\Services\ParseTransactions\Dto\StartParseTransactionsHtmlDto;
use App\Banking\Services\ParseTransactions\Exceptions\ParseAccountSuccessAttemptException;
use App\Banking\Services\ParseTransactions\TransactionsHtmlParseService;
use App\PersonalData\Repositories\User\UserRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Bus;
use Throwable;

/**
 * В этом сервисе все что связано с парсингом html через ИИ
 */
class TransactionsHtmlParseServiceImpl implements TransactionsHtmlParseService
{
    private const TEST_USERNAME = 'michael_magomedov';
    private const MAX_SUCCESS_LOAD_PER_HOUR = 100;

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly AiPromptRepository  $aiPromptRepository,
        private readonly UserRepository      $userRepository,
        private readonly LoadService         $loadService,
        private readonly LoadRepository      $loadRepository,
        private readonly AccountRepository   $accountRepository,
    )
    {
    }

    public function startParse(StartParseTransactionsHtmlDto $parseParams): void
    {
        try {
            $this->connection->beginTransaction();
            $account = $this->accountRepository->findById($parseParams->getAccountId());
            $user = $this->userRepository->findById($account->getUserId());

            // создаем загрузку что бы в ней логировать статус, что бы при не успехе восстановить данные из старой загрузки
            // плюс часть параметров сторим в load например userId или accountId что бы не гонять все эти данные через job и сервисы
            if ($parseParams->getLoadId() !== null) {
                $load = $this->loadRepository->findById($parseParams->getLoadId());
                $this->loadService->updateStatus(new UpdateLoadStatusDto(
                    $load->getId(),
                    LoadStatusEnum::PENDING
                ));
            } else {
                $load = $this->loadService->create(new CreateLoadDto(
                    $account->getUserId(),
                    null,
                    LoadTypeEnum::PARSE_TRANSACTION
                ));
            };

            $successLoadCountPerHour = $this->loadRepository->findCountSuccessLoadByPrevHour($account->getId());
            if ($successLoadCountPerHour > self::MAX_SUCCESS_LOAD_PER_HOUR && $user->getUsername() !== self::TEST_USERNAME) {
                throw new ParseAccountSuccessAttemptException(self::MAX_SUCCESS_LOAD_PER_HOUR);
            }

            /** копия кода из @see TransactionsFileParseServiceImpl::startParse */
            $saveTransactionDataJob = new SaveParsedTransactionsDataJob($load->getId());
            $pendingChain = $this->aiPromptRepository->prepareTransactionsDataAndRunAction(
                $parseParams->getHtml(),
                $load,
                $saveTransactionDataJob
            );
            // переделываем pending chain на массив job что бы отправить их потом в batch (небольшой костылек)
            $transactionsParseBatch[] = [$pendingChain->job, ...$pendingChain->chain];

            // в batch и рулим транзакциями в конце (добавляем к ним обработки и кетчи)
            // тут получается такая конструкция [prompt,run,prompt],[prompt,run,prompt] итд
            // каждая цепочка выполняется последовательно но все цепочки выполняются паралельно
            Bus::batch($transactionsParseBatch)->finally(function () use ($load) {
                dispatch(new ProcessFinalTransactionFileLoadJob($load->getId()));
            })->dispatch();
            $this->connection->commit();

        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

}
