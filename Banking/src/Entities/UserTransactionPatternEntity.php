<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

final class UserTransactionPatternEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string $id,
        private readonly string $destination,
        private readonly string $userId,
        private string          $fromTransactionId,
        private ?string         $categoryId = null,
        private ?string         $codeId = null,
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getDestination(): string {
        return $this->destination;
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getFromTransactionId(): string {
        return $this->fromTransactionId;
    }

    public function getCategoryId(): ?string {
        return $this->categoryId;
    }

    public function getCodeId(): ?string {
        return $this->codeId;
    }

    public function withFromTransactionId(string $fromTransactionId): self {
        $new = clone $this;
        $new->fromTransactionId = $fromTransactionId;
        return $new;
    }

    public function withCategoryId(?string $categoryId): self {
        $new = clone $this;
        $new->categoryId = $categoryId;
        return $new;
    }

    public function withCodeId(?string $codeId): self {
        $new = clone $this;
        $new->codeId = $codeId;
        return $new;
    }
}
