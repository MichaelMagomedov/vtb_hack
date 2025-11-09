<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Banking\Enums\TransactionTypeEnum;
use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;
use DateTime;

final class TransactionEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string     $id,
        private string              $accountId,
        private float               $amount,
        private DateTime            $date,
        private TransactionTypeEnum $type,
        private int                 $order,
        private string              $shortDesc,
        private ?string             $loadId,
        private ?string             $operationCode = null,
        private ?string             $desc = null,
        private ?string             $destination = null,
        private readonly ?int       $mcc = null,
        private ?string             $categoryId = null,
        private ?string             $codeId = null,
        private ?string             $color = null,
        private string              $userId,
        private ?string             $mccReason = null,
        private bool                $verified = false,
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

    public function getOrder(): int {
        return $this->order;
    }

    public function getLoadId(): ?string {
        return $this->loadId;
    }

    public function getOperationCode(): ?string {
        return $this->operationCode;
    }

    public function getDesc(): ?string {
        return $this->desc;
    }

    public function getShortDesc(): string {
        return $this->shortDesc;
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

    public function getCodeId(): ?string {
        return $this->codeId;
    }

    public function getColor(): ?string {
        return $this->color;
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getMccReason(): ?string {
        return $this->mccReason;
    }

    public function isVerified(): bool {
        return $this->verified;
    }

    public function withAmount(float $amount): self {
        $new = clone $this;
        $new->amount = $amount;
        return $new;
    }


    public function withAccountId(string $accountId): self {
        $new = clone $this;
        $new->accountId = $accountId;
        return $new;
    }

    public function withDate(DateTime $date): self {
        $new = clone $this;
        $new->date = $date;
        return $new;
    }

    public function withType(TransactionTypeEnum $type): self {
        $new = clone $this;
        $new->type = $type;
        return $new;
    }

    public function withOrder(int $order): self {
        $new = clone $this;
        $new->order = $order;
        return $new;
    }

    public function withCategoryId(?string $categoryId): self {
        $new = clone $this;
        $new->categoryId = $categoryId;
        return $new;
    }

    public function withDesc(?string $desc): self {
        $new = clone $this;
        $new->desc = $desc;
        return $new;
    }

    public function withShortDesc(string $shortDesc): self {
        $new = clone $this;
        $new->shortDesc = $shortDesc;
        return $new;
    }

    public function withDestination(?string $destination): self {
        $new = clone $this;
        $new->destination = $destination;
        return $new;
    }

    public function withCodeId(?string $codeId): self {
        $new = clone $this;
        $new->codeId = $codeId;
        return $new;
    }

    public function withColor(?string $color): self {
        $new = clone $this;
        $new->color = $color;
        return $new;
    }

    public function withOperationCode(?string $operationCode): self {
        $new = clone $this;
        $new->operationCode = $operationCode;
        return $new;
    }

    public function withVerified(bool $verified = false): self {
        $new = clone $this;
        $new->verified = $verified;
        return $new;
    }
}
