<?php

declare(strict_types=1);

namespace App\Banking\Repositories\TransactionCode;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Этот вид репозиториев используется для контроллеров
 * что бы уметь отдавать модели вместе с зависимостями
 * ведь зависимости нельзя получить из entity
 */
interface TransactionCodeViewRepository
{
    public function findAllWithRelations(?string $categoryId, ?string $query = null): LengthAwarePaginator;
}

