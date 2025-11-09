<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Banking\Enums\AccountTypeEnum;
use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

final class AccountEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private string          $number,
        private string          $name,
        private int             $order,
        private AccountTypeEnum $type,
        private string          $currencyId,
        private ?string         $bankId = null,
        private ?string         $bankReason = null,
        private ?string         $currencyReason = null,
    )
    {
    }


    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }


    public function getNumber(): string
    {
        return $this->number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getCurrencyId(): string
    {
        return $this->currencyId;
    }

    public function getBankId(): ?string
    {
        return $this->bankId;
    }

    public function getType(): AccountTypeEnum
    {
        return $this->type;
    }

    public function getBankReason(): ?string
    {
        return $this->bankReason;
    }

    public function getCurrencyReason(): ?string
    {
        return $this->currencyReason;
    }

    public function withOrder(int $order): self
    {
        $new = clone $this;
        $new->order = $order;
        return $new;
    }

    public function withName(string $name): self
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }

    public function withType(AccountTypeEnum $type): self
    {
        $new = clone $this;
        $new->type = $type;
        return $new;
    }

    public function withNumber(string $number): self
    {
        $new = clone $this;
        $new->number = $number;
        return $new;
    }

    public function withCurrencyId(string $currencyId): self
    {
        $new = clone $this;
        $new->currencyId = $currencyId;
        return $new;
    }

    public function withBankId(?string $bankId): self
    {
        $new = clone $this;
        $new->bankId = $bankId;
        return $new;
    }

    public function withBankReason(?string $bankReason): self
    {
        $new = clone $this;
        $new->bankReason = $bankReason;
        return $new;
    }

    public function withCurrencyReason(?string $currencyReason): self
    {
        $new = clone $this;
        $new->currencyReason = $currencyReason;
        return $new;
    }
}
