<?php

declare(strict_types=1);

namespace App\Banking\Events;

use App\Banking\Entities\TransactionEntity;
use Illuminate\Foundation\Events\Dispatchable;

final class TransactionChangeEvent
{
    use Dispatchable;

    public function __construct(
        private readonly TransactionEntity $transaction
    )
    {
    }

    public function getTransaction(): TransactionEntity
    {
        return $this->transaction;
    }
}
