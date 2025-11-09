<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Bank;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Этот вид репозиториев используется для контроллеров
 * что бы уметь отдавать модели вместе с зависимостями
 * ведь зависимости нельзя получить из entity
 */
interface BankViewRepository
{
    public function findAllWithRelations(?string $query = null): LengthAwarePaginator;
}

