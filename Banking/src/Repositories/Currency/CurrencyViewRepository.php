<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Currency;

use App\Banking\Models\AccountModel;

/**
 * Этот вид репозиториев используется для контроллеров
 * что бы уметь отдавать модели вместе с зависимостями
 * ведь зависимости нельзя получить из entity
 */
interface CurrencyViewRepository
{
    public function findAllWithRelations(): array;
}

