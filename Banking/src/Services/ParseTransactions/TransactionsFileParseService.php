<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions;

use App\Banking\Entities\AccountEntity;
use App\Banking\Services\ParseTransactions\Dto\CreateAccountByParsedDataParamsDto;
use App\Banking\Services\ParseTransactions\Dto\StartParseTransactionsFileDto;
use App\Banking\Services\ParseTransactions\Exceptions\ParseAccountNumberNotFoundException;
use App\Banking\Services\ParseTransactions\Exceptions\ParseAccountUnknownException;
use App\Banking\Services\ParseTransactions\Exceptions\ParseTransactionFileExtensionException;

interface TransactionsFileParseService
{
    /**
     * Запускает все очереди на парсинг информации из файла
     *
     * @throws ParseTransactionFileExtensionException
     */
    public function startParse(StartParseTransactionsFileDto $parseParams): void;

    /**
     * Метод дергается из очереди и вызывает парсинг транзакций
     * а дальше уже мы сохраняем найденные транзакции в TransactionsParseService
     *
     * @throws ParseAccountUnknownException
     * @throws ParseAccountNumberNotFoundException
     */
    public function createAccountByParsedData(CreateAccountByParsedDataParamsDto $params): AccountEntity;
}
