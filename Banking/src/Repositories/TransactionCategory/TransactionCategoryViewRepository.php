<?php

declare(strict_types=1);

namespace App\Banking\Repositories\TransactionCategory;

use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Этот вид репозиториев используется для контроллеров
 * что бы уметь отдавать модели вместе с зависимостями
 * ведь зависимости нельзя получить из entity
 */
interface TransactionCategoryViewRepository
{
    public function findAllWithRelations(string $userId, ?string $codeId = null, ?string $query = null): LengthAwarePaginator;

    public function findAllByUser(string $userId, DateTime $from, DateTime $to): array;
}

