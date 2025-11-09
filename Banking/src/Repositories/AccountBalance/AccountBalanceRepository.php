<?php

declare(strict_types=1);

namespace App\Banking\Repositories\AccountBalance;

use App\Banking\Entities\AccountBalanceEntity;
use DateTime;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface AccountBalanceRepository
{
    public function findById(string $id): ?AccountBalanceEntity;

    public function save(AccountBalanceEntity $entity): AccountBalanceEntity;

    public function deleteByDate(string $userId, DateTime $balanceDate, string $excludeId = null): void;

    public function findMaxOrder(string $userId): ?int;

    public function deleteByLoadId(string $loadId): void;

    public function restoreByLoadId(string $loadId): void;

    public function update(AccountBalanceEntity $entity): AccountBalanceEntity;

    public function belongsUser(string $id, string $userId): bool;

    public function findByUser(string $userId, DateTime $from, DateTime $to): array;

    public function getFirstAccountBalance(string $userId, DateTime $to): ?AccountBalanceEntity;

    public function getLastAccountBalance(string $userId, DateTime $to): ?AccountBalanceEntity;

    /** @return AccountBalanceEntity[] */
    public function findByIds(array $ids): array;
}

