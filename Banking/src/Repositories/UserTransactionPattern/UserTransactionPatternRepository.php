<?php

declare(strict_types=1);

namespace App\Banking\Repositories\UserTransactionPattern;

use App\Banking\Entities\UserTransactionPatternEntity;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface UserTransactionPatternRepository
{
    public function save(UserTransactionPatternEntity $entity): UserTransactionPatternEntity;

    public function update(UserTransactionPatternEntity $entity): UserTransactionPatternEntity;

    public function delete(string $id): void;

    public function findByUserIdAndDestination(string $userId, string $destination): ?UserTransactionPatternEntity;

    public function findById(string $id): ?UserTransactionPatternEntity;
}

