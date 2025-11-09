<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions;

use App\Banking\Services\ParseTransactions\Dto\StartParseTransactionsHtmlDto;

interface TransactionsHtmlParseService
{
    /**
     * Запускает все очереди на парсинг информации из html текста
     */
    public function startParse(StartParseTransactionsHtmlDto $parseParams): void;
}
