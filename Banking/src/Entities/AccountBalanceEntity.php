<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;
use DateTime;

final class AccountBalanceEntity
{
    use HasHistoryFields;

    public function __construct(
        private string   $id,
        private float    $balance,
        private DateTime $balanceDate,
        private int      $order,
        private string   $userId,
        // если accountId - null это общий баланс на текущий день
        // то есть не для конкретного аккаунта а общий баланс всех аккаунтов
        private ?string  $accountId = null,
        private ?string  $loadId = null,
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

    public function getAccountId(): ?string {
        return $this->accountId;
    }

    public function getOrder(): int {
        return $this->order;
    }

    public function getLoadId(): ?string {
        return $this->loadId;
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function withBalance(float $balance): self {
        $new = clone $this;
        $new->balance = $balance;
        return $new;
    }

    public function withBalanceDate(DateTime $balanceDate): self {
        $new = clone $this;
        $new->balanceDate = $balanceDate;
        return $new;
    }

    public function withOrder(int $order): self {
        $new = clone $this;
        $new->order = $order;
        return $new;
    }
}
