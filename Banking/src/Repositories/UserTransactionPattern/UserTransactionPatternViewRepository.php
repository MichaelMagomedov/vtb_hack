<?php

declare(strict_types=1);

namespace App\Banking\Repositories\UserTransactionPattern;

use App\Banking\Models\UserTransactionPatternModel;

interface UserTransactionPatternViewRepository
{
    public function findByUserIdWithRelations(string $userId, string $destination): ?UserTransactionPatternModel;
}

