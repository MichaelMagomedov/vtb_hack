<?php

declare(strict_types=1);

namespace App\Banking\Services\Transaction\Dto;

use App\Banking\Enums\TransactionTypeEnum;
use DateTime;

final class UpdateTransactionDto
{
    public function __construct(
        private readonly string                       $id,
        private readonly string                       $accountId,
        private readonly float                        $amount,
        private readonly DateTime                     $date,
        private readonly TransactionTypeEnum          $type,
        private readonly string                       $shortDesc,
        private readonly ?string                      $desc = null,
        private readonly ?string                      $destination = null,
        private readonly ?string                      $categoryId = null,
        private readonly ?string                      $codeId = null,
        private readonly ?string                      $color = null,
        private readonly ?UpdateTransactionPatternDto $patternParams,
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getAccountId(): string {
        return $this->accountId;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getDate(): DateTime {
        return $this->date;
    }

    public function getType(): TransactionTypeEnum {
        return $this->type;
    }

    public function getShortDesc(): string {
        return $this->shortDesc;
    }

    public function getDesc(): ?string {
        return $this->desc;
    }

    public function getDestination(): ?string {
        return $this->destination;
    }

    public function getCategoryId(): ?string {
        return $this->categoryId;
    }

    public function getCodeId(): ?string {
        return $this->codeId;
    }

    public function getColor(): ?string {
        return $this->color;
    }

    public function getPatternParams(): ?UpdateTransactionPatternDto {
        return $this->patternParams;
    }

}
