<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions;

use App\Banking\Entities\TransactionEntity;
use App\Banking\Services\ParseTransactions\Dto\CreateTransactionsByParsedDataParamsDto;

/**
 * Это сервис, который содержит общие методы дл любого парсинга (html или file)
 */
interface TransactionsParseService
{
    /**
     * Метод дергается job
     *
     * @return TransactionEntity[]
     */
    public function createTransactionByParsedData(CreateTransactionsByParsedDataParamsDto $params): array;


    /**
     * Метод дергается из job
     * */
    public function revertTransactionIfParseFail(string $loadId): void;
}
