<?php

declare(strict_types=1);

namespace App\Banking\Repositories\AccountBalance;

use App\Banking\Models\AccountBalanceModel;
use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AccountBalanceViewRepository
{
    public function findByUserIdWithRelations(string $userId, ?string $accountId = null): LengthAwarePaginator;

    /** Тоже самое что и findByUserIdWithRelations только без пагинации  */
    public function findAllByUserIdWithRelations(string $userId, DateTime $from = null, DateTime $to = null): array;

    public function findByIdWithRelations(string $id): ?AccountBalanceModel;

}

