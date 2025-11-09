<?php

declare(strict_types=1);

namespace App\Banking\Services\TransactionCode;

use App\Banking\Entities\TransactionCodeEntity;
use App\Banking\Services\TransactionCode\Dto\CreateTransactionCodeDto;

interface TransactionCodeService
{
    /**
     * @param CreateTransactionCodeDto[] $codes
     * @return TransactionCodeEntity[]
     */
    public function saveCodes(array $codes): array;
}
