<?php

declare(strict_types=1);

namespace App\Banking\Services\Transaction\Dto;

use App\Banking\Enums\TransactionTypeEnum;
use DateTime;
use phpseclib3\Crypt\EC\Curves\brainpoolP160r1;

final class CreateTransactionDto
{
    public function __construct(
        private readonly string              $accountId,
        private readonly float               $amount,
        private readonly string              $shortDestination,
        private readonly ?string             $operationCode = null,
        private readonly ?string             $description = null,
        private readonly DateTime            $date,
        private readonly TransactionTypeEnum $type,
        private readonly ?string             $loadId,
        private readonly ?string             $destination = null,
        private readonly ?int                $mcc = null,
        private readonly ?string             $categoryId = null,
        private ?int                         $order = null,
        private readonly ?string             $codeId = null,
        private readonly ?string             $color = null,
        private readonly ?string             $mccReason = null
    ) {
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

    public function getLoadId(): ?string {
        return $this->loadId;
    }

    public function getShortDestination(): string {
        return $this->shortDestination;
    }

    public function getOperationCode(): ?string {
        return $this->operationCode;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function getDestination(): ?string {
        return $this->destination;
    }

    public function getMcc(): ?int {
        return $this->mcc;
    }

    public function getCategoryId(): ?string {
        return $this->categoryId;
    }

    public function getOrder(): ?int {
        return $this->order;
    }

    public function getCodeId(): ?string {
        return $this->codeId;
    }

    public function getColor(): ?string {
        return $this->color;
    }

    public function isUseUserTransactionPatterns(): bool {
        return $this->useUserTransactionPatterns;
    }

    public function getMccReason(): ?string {
        return $this->mccReason;
    }

    // немного костылей, ведь это DTO но подмениваем данные некоторые вычисляемые параметры
    public function withOrder(int $order): self {
        $new = clone $this;
        $new->order = $order;
        return $new;
    }
}
