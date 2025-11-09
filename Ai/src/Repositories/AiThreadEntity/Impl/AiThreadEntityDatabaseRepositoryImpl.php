<?php

declare(strict_types=1);

namespace App\Ai\Repositories\AiThreadEntity\Impl;

use App\Ai\Entities\AiThreadEntity;
use App\Ai\Models\AiThreadModel;
use App\Ai\Repositories\AiThreadEntity\AiThreadEntityRepository;
use App\Root\Mappers\EloquentEntityMapper;

final class AiThreadEntityDatabaseRepositoryImpl implements AiThreadEntityRepository
{
    private EloquentEntityMapper $eloquentEntityMapper;

    public function __construct(EloquentEntityMapper $eloquentEntityMapper) {
        $this->eloquentEntityMapper = $eloquentEntityMapper;
    }

    public function save(AiThreadEntity $entity): AiThreadEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, AiThreadModel::class);
        $model->saveOrFail();

        return $this->eloquentEntityMapper->toEntity($model, AiThreadEntity::class);
    }

    public function update(AiThreadEntity $entity): AiThreadEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, AiThreadModel::class);
        $model->exists = true;
        $model->updateOrFail();

        return $this->eloquentEntityMapper->toEntity($model, AiThreadEntity::class);
    }

    public function findByThreadId(string $threadId): ?AiThreadEntity {
        $model = AiThreadModel::query()->where('thread', $threadId)->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, AiThreadEntity::class);

    }
}

