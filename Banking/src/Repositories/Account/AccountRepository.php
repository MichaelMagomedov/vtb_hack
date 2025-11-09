<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Account;

use App\Banking\Entities\AccountEntity;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface AccountRepository
{
    public function save(AccountEntity $entity): AccountEntity;

    public function update(AccountEntity $entity): AccountEntity;

    public function delete(string $id): void;

    public function findByNumber(string $userId, string $number, string $excludeId = null): ?AccountEntity;

    public function findById(string $id): ?AccountEntity;

    /** @return AccountEntity[] */
    public function findByIds(array $ids): array;

    public function findMaxOrder(string $userId): ?int;
}

