<?php

declare(strict_types=1);

namespace App\Banking\Repositories\UserTransactionPattern\Impl;

use App\Banking\Entities\UserTransactionPatternEntity;
use App\Banking\Models\UserTransactionPatternModel;
use App\Banking\Repositories\UserTransactionPattern\UserTransactionPatternRepository;
use App\Banking\Repositories\UserTransactionPattern\UserTransactionPatternViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class UserTransactionPatternDatabaseRepositoryImpl implements UserTransactionPatternRepository, UserTransactionPatternViewRepository
{
    private EloquentEntityMapper $eloquentEntityMapper;

    public function __construct(EloquentEntityMapper $eloquentEntityMapper) {
        $this->eloquentEntityMapper = $eloquentEntityMapper;
    }

    public function save(UserTransactionPatternEntity $entity): UserTransactionPatternEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, UserTransactionPatternModel::class);
        $model->saveOrFail();

        return $this->eloquentEntityMapper->toEntity($model, UserTransactionPatternEntity::class);
    }

    public function update(UserTransactionPatternEntity $entity): UserTransactionPatternEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, UserTransactionPatternModel::class);
        $model->exists = true;
        $model->updateOrFail();

        return $this->eloquentEntityMapper->toEntity($model, UserTransactionPatternEntity::class);
    }

    public function delete(string $id): void {
        UserTransactionPatternModel::where('id', $id)->delete();
    }

    public function findByUserIdAndDestination(string $userId, string $destination): ?UserTransactionPatternEntity {
        $destination = mb_strtolower($destination);
        $model = UserTransactionPatternModel::query()
            ->where('user_id', $userId)
            ->whereHas('transaction.account', function (Builder $builder) use ($userId) {
                $builder
                    ->withTrashed()
                    ->whereNull('deleted_at');
            })
            ->whereRaw(DB::raw("lower(destination) = lower('$destination')"))
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, UserTransactionPatternEntity::class);
    }

    public function findById(string $id): ?UserTransactionPatternEntity {
        $model = UserTransactionPatternModel::query()
            ->where('id', $id)
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, UserTransactionPatternEntity::class);
    }

    public function findByUserIdWithRelations(string $userId, string $destination): ?UserTransactionPatternModel {
        return UserTransactionPatternModel::query()
            ->with(['category', 'code'])
            ->where('user_id', $userId)
            ->whereHas('transaction.account', function (Builder $builder) use ($userId) {
                $builder
                    ->withTrashed()
                    ->whereNull('deleted_at');
            })
            ->whereRaw(DB::raw("lower(destination) = lower('$destination')"))
            ->get()
            ->first();
    }
}

