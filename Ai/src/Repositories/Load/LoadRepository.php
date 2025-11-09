<?php

declare(strict_types=1);

namespace App\Ai\Repositories\Load;

use App\Ai\Entities\LoadEntity;
use App\Ai\Enums\LoadTypeEnum;
use DateTime;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface LoadRepository
{
    public function save(LoadEntity $entity): LoadEntity;

    public function update(LoadEntity $entity, array $attributes = []): LoadEntity;

    /** Важно использовать этот метода, когда есть concurrency в job  */
    public function updateSpecificAttributes(LoadEntity $entity, array $attributes = []): LoadEntity;

    public function findById(string $id): ?LoadEntity;

    public function findUserLoadStack(string $userId, LoadTypeEnum $type, string $accountId = null): array;

    public function findCountSuccessLoadByPrevHour(string $accountId): int;

    public function findPrevSuccessLoad(string $id, string $userId, LoadTypeEnum $type): ?LoadEntity;

    public function findPrevSuccessLoadByAccount(string $id, string $accountId): ?LoadEntity;

    public function findHungLoads(DateTime $from, array $statuses, int $limit = 1000): array;

    public function addInputCharsCount(string $id, int $count): void;

    public function addInputWordsCount(string $id, int $count): void;

    public function addOutputCharsCount(string $id, int $count): void;

    public function addOutputWordsCount(string $id, int $count): void;
}

