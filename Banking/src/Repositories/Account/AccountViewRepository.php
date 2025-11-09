<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Account;

use App\Banking\Models\AccountModel;

/**
 * Этот вид репозиториев используется для контроллеров
 * что бы уметь отдавать модели вместе с зависимостями
 * ведь зависимости нельзя получить из entity
 */
interface AccountViewRepository
{
    public function findByUserIdWithRelations(string $userId): array;

    public function findByIdWithRelations(string $id): ?AccountModel;
}

