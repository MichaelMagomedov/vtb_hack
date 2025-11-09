<?php

declare(strict_types=1);

namespace App\Banking\Services\AccountBalance\Dto;

use DateTime;

final class CreateAccountBalanceDto
{
    public function __construct(
        private readonly float    $balance,
        private readonly DateTime $balanceDate,
        private readonly string   $userId,
        private readonly ?string  $accountId = null,
        private readonly ?string  $loadId = null,
    ) {
    }

    public function getBalance(): float {
        return $this->balance;
    }

    public function getBalanceDate(): DateTime {
        return $this->balanceDate;
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getAccountId(): ?string {
        return $this->accountId;
    }

    public function getLoadId(): ?string {
        return $this->loadId;
    }
}
