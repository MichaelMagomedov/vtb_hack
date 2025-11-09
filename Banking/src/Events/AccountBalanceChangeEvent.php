<?php

declare(strict_types=1);

namespace App\Banking\Events;

use App\Banking\Entities\AccountBalanceEntity;
use Illuminate\Foundation\Events\Dispatchable;

final class AccountBalanceChangeEvent
{
    use Dispatchable;

    public function __construct(
        private readonly AccountBalanceEntity $accountBalance
    ) {
    }

    public function getAccountBalance(): AccountBalanceEntity {
        return $this->accountBalance;
    }
}
