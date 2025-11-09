<?php

declare(strict_types=1);

namespace App\Banking\Services\TransactionCode\Dto;

final class CreateTransactionCodeDto
{
    public function __construct(
        private readonly string $name,
        private readonly int $code,
        private readonly string  $categoryName,
        private readonly ?string $desc = null,
    ) {
    }

    public function getName(): string {
        return $this->name;
    }

    public function getCode(): int {
        return $this->code;
    }

    public function getCategoryName(): string {
        return $this->categoryName;
    }

    public function getDesc(): ?string {
        return $this->desc;
    }
}
