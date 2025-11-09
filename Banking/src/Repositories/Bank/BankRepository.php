<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Bank;

use App\Banking\Entities\BankEntity;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface BankRepository
{
    public function findById(string $id): ?BankEntity;
}

