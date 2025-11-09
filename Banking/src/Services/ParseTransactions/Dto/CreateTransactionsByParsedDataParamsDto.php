<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Dto;

final class CreateTransactionsByParsedDataParamsDto
{
    public function __construct(
        private readonly array  $parsedData,
        private readonly string $loadId,
    ) {
    }

    public function getParsedData(): array {
        return $this->parsedData;
    }

    public function getLoadId(): string {
        return $this->loadId;
    }
}
