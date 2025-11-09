<?php

declare(strict_types=1);

namespace App\Banking\Services\Account\Dto;

use App\Banking\Enums\AccountTypeEnum;

final class UpdateAccountDto
{
    public function __construct(
        private readonly string          $id,
        private readonly string          $name,
        private readonly AccountTypeEnum $type,
        private readonly int             $number,
        private readonly ?string         $bankId = null,
        private readonly ?string         $currencyId = null,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): AccountTypeEnum
    {
        return $this->type;
    }


    public function getBankId(): ?string
    {
        return $this->bankId;
    }

    public function getCurrencyId(): ?string
    {
        return $this->currencyId;
    }
}
