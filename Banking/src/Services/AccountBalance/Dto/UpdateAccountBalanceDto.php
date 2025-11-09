<?php

declare(strict_types=1);

namespace App\Banking\Services\AccountBalance\Dto;

use DateTime;

final class UpdateAccountBalanceDto
{
    public function __construct(
        private readonly string $id,
        private readonly float $balance,
        private readonly DateTime $balanceDate,
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getBalance(): float {
        return $this->balance;
    }

    public function getBalanceDate(): DateTime {
        return $this->balanceDate;
    }
}
