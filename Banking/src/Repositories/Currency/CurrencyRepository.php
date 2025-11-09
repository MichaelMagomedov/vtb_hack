<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Currency;

use App\Banking\Entities\CurrencyEntity;
use App\Banking\Enums\CurrencyEnum;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface CurrencyRepository
{
    public function findByCode(CurrencyEnum $currencyEnum): ?CurrencyEntity;

    public function findById(string $id): ?CurrencyEntity;
}

