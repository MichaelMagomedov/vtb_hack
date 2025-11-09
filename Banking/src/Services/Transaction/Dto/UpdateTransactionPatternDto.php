<?php

declare(strict_types=1);

namespace App\Banking\Services\Transaction\Dto;

final class UpdateTransactionPatternDto
{
    public function __construct(
        private readonly ?string $id = null,
        private readonly ?string $categoryId = null,
        private readonly ?string $codeId = null,
    ) {
    }

    public function getId(): ?string {
        return $this->id;
    }

    public function getCategoryId(): ?string {
        return $this->categoryId;
    }

    public function getCodeId(): ?string {
        return $this->codeId;
    }

}
