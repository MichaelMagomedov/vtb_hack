<?php

declare(strict_types=1);

namespace App\Banking\Services\Account\Dto;

use App\Banking\Enums\AccountTypeEnum;

final class GetOrCreateAccountDto
{
    public function __construct(
        private readonly string          $userId,
        private readonly string          $number,
        private readonly AccountTypeEnum $type,
        private readonly ?string         $bankId = null,
        private readonly ?string         $name = null,
        private readonly ?string         $currencyId = null,
        private readonly ?string         $bankReason = null,
        private readonly ?string         $currencyReason = null,
    ) {
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getNumber(): string {
        return $this->number;
    }

    public function getCurrencyId(): ?string {
        return $this->currencyId;
    }

    public function getType(): AccountTypeEnum {
        return $this->type;
    }

    public function getBankId(): ?string {
        return $this->bankId;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getBankReason(): ?string {
        return $this->bankReason;
    }

    public function getCurrencyReason(): ?string {
        return $this->currencyReason;
    }
}
