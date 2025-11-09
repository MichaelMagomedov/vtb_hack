<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Dto;

final class StartParseTransactionsHtmlDto
{
    public function __construct(
        private readonly string $accountId,
        private readonly string $html,
        // передаем load id что бы все транзакции могли загружаться в рамках одного load
        // что бы в сохранение транзакций TransactionServiceImpl->saveTransactionsAfterParse
        // а именно метод findDateUntilWhichParseNotAvailable корректно
        private readonly ?string $loadId = null
    )
    {
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getLoadId(): ?string
    {
        return $this->loadId;
    }
}
