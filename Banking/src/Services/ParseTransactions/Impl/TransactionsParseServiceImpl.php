<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Impl;

use App\Ai\Repositories\AiPrompt\Impl\AiPromptChatGptRepositoryImpl;
use App\Ai\Repositories\Load\LoadRepository;
use App\Banking\Enums\TransactionTypeEnum;
use App\Banking\Services\AccountBalance\AccountBalanceService;
use App\Banking\Services\ParseTransactions\Dto\CreateTransactionsByParsedDataParamsDto;
use App\Banking\Services\ParseTransactions\Exceptions\ParseOperationsNotFoundException;
use App\Banking\Services\ParseTransactions\TransactionsParseService;
use App\Banking\Services\Transaction\Dto\CreateTransactionDto;
use App\Banking\Services\Transaction\TransactionService;
use DateTime;

/**
 * В этом сервисе все общие методы которые используются во всех видо варсинга
 */
class TransactionsParseServiceImpl implements TransactionsParseService
{
    public function __construct(
        private readonly LoadRepository          $loadRepository,
        private readonly TransactionService      $transactionService,
        private readonly AccountBalanceService   $accountBalanceService,
    ) {
    }


    /** В самих методах ничего не осхраняем а только вызываем сохранение в сущностей в других сервисах */
    public function createTransactionByParsedData(CreateTransactionsByParsedDataParamsDto $params): array {
        $load = $this->loadRepository->findById($params->getLoadId());

        // сохраняем полученные транзакции от нейросети
        $parsedData = $params->getParsedData();
        if (empty($parsedData['operations'])) {
            throw new ParseOperationsNotFoundException($parsedData);
        }
        $operationsData = [];
        foreach ($parsedData['operations'] as $operation) {

            $amount = (float)filter_var($operation['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $mcc = !empty($operation['mcc']) ? (int)filter_var($operation['mcc'], FILTER_SANITIZE_NUMBER_INT) : null;
            $date = new DateTime($operation['datetime']);

            /** @see AiPromptChatGptRepositoryImpl::prepareTransactionsDataAndRunAction() */
            $operationsData[] = new CreateTransactionDto(
                $load->getAccountId(),
                $amount,
                $operation['shortDesc'] ?? '',
                !empty($operation['operationCode']) ? $operation['operationCode'] : null,
                !empty($operation['desc']) ? $operation['desc'] : null,
                $date,
                !empty($operation['type']) ? TransactionTypeEnum::from($operation['type']) : TransactionTypeEnum::SIMPLE,
                $load->getId(),
                !empty($operation['merchantAlias']) ? $operation['merchantAlias'] : null,
                $mcc,
                null,
                null,
                null,
                !empty($operation['merchantColor']) ? $operation['merchantColor'] : null,
                !empty($operation['mccReason']) ? $operation['mccReason'] : null,
            );
        }
        $transactions = $this->transactionService->saveTransactionsAfterParse($operationsData, $params->getLoadId());

        return $transactions;
    }

    public function revertTransactionIfParseFail(string $loadId): void {
        $this->transactionService->deleteAllTransactionsIfAndRestoreOld($loadId);
        $this->accountBalanceService->deleteAllBalancesIfAndRestoreOld($loadId);
    }
}
