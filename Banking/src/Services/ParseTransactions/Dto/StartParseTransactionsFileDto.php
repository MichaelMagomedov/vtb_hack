<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Dto;

final class StartParseTransactionsFileDto
{
    public function __construct(
        private readonly string $userId,
        private readonly string $chatId,
        private readonly string $path
    ) {
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getChatId(): string {
        return $this->chatId;
    }

    public function getPath(): string {
        return $this->path;
    }
}
