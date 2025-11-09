<?php

declare(strict_types=1);

namespace App\Ai\Repositories\AiThreadEntity;

use App\Ai\Entities\AiThreadEntity;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface AiThreadEntityRepository
{
    public function save(AiThreadEntity $entity): AiThreadEntity;

    public function update(AiThreadEntity $entity): AiThreadEntity;

    public function findByThreadId(string $threadId): ?AiThreadEntity;
}

