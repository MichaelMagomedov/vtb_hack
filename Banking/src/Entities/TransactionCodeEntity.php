<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

final class TransactionCodeEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string  $id,
        private readonly int     $code,
        private readonly string  $categoryId,
        private readonly string  $name,
        private readonly ?string $desc = null
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getCode(): int {
        return $this->code;
    }

    public function getCategoryId(): string {
        return $this->categoryId;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDesc(): ?string {
        return $this->desc;
    }
}
