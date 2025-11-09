<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Dto;

final class CreateAccountByParsedDataParamsDto
{
    public function __construct(
        private readonly array  $parsedData,
        private readonly string $loadId,
        private readonly string $pdfPath
    ) {
    }

    public function getParsedData(): array {
        return $this->parsedData;
    }

    public function getLoadId(): string {
        return $this->loadId;
    }

    public function getPdfPath(): string {
        return $this->pdfPath;
    }

}
