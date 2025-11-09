<?php

declare(strict_types=1);

namespace App\Banking\Events;

use App\Banking\Entities\AccountEntity;
use Illuminate\Foundation\Events\Dispatchable;

final class AccountChangeEvent
{
    use Dispatchable;

    public function __construct(
        private readonly AccountEntity $accountEntity
    )
    {
    }

    public function getAccountEntity(): AccountEntity
    {
        return $this->accountEntity;
    }
}
