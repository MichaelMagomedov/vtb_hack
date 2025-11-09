<?php

declare(strict_types=1);

namespace App\Banking\Services\UserTransactionPattern;

use App\Banking\Entities\TransactionEntity;
use App\Banking\Entities\UserTransactionPatternEntity;
use App\Banking\Services\Transaction\Dto\UpdateTransactionPatternDto;

interface UserTransactionPatternService
{
    public function update(UpdateTransactionPatternDto $patternParams, TransactionEntity $transaction): ?UserTransactionPatternEntity;

    public function deleteByTransaction(TransactionEntity $transaction): void;
}
