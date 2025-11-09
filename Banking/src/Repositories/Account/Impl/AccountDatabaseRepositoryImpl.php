<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Account\Impl;

use App\Banking\Entities\AccountEntity;
use App\Banking\Models\AccountModel;
use App\Banking\Repositories\Account\AccountRepository;
use App\Banking\Repositories\Account\AccountViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use Illuminate\Support\Facades\DB;

final class AccountDatabaseRepositoryImpl implements AccountRepository, AccountViewRepository
{
    public function __construct(
        private readonly EloquentEntityMapper $eloquentEntityMapper
    )
    {
    }

    public function save(AccountEntity $entity): AccountEntity
    {
        $model = $this->eloquentEntityMapper->toModel($entity, AccountModel::class);
        $model->saveOrFail();

        return $this->eloquentEntityMapper->toEntity($model, AccountEntity::class);
    }

    public function update(AccountEntity $entity): AccountEntity
    {
        $model = $this->eloquentEntityMapper->toModel($entity, AccountModel::class);
        $model->exists = true;
        $model->updateOrFail();

        return $this->eloquentEntityMapper->toEntity($model, AccountEntity::class);
    }

    public function delete(string $id): void
    {
        AccountModel::where('id', $id)->delete();
    }

    public function findByNumber(string $userId, string $number, string $excludeId = null): ?AccountEntity
    {
        $builder = AccountModel::query()
            ->where('user_id', $userId)
            ->where('number', $number);
        if ($excludeId !== null) {
            $builder = $builder->whereNotIn('number', [$excludeId]);
        }
        $model = $builder->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, AccountEntity::class);
    }

    public function findById(string $id): ?AccountEntity
    {
        $model = AccountModel::query()
            ->where('id', $id)
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, AccountEntity::class);
    }

    public function findByIds(array $ids): array
    {
        $accountModels = AccountModel::query()->whereIn('id', $ids)->get();

        return $this->eloquentEntityMapper->toEntityArray($accountModels, AccountEntity::class);
    }


    public function findMaxOrder(string $userId): ?int
    {
        $result = DB::selectOne('
            SELECT MAX(a.order) as max_order
            FROM accounts a
            WHERE a.deleted_at IS NULL
            AND a.user_id = :user_id', [
            'user_id' => $userId,
        ]);

        return $result->max_order ? (int)$result->max_order : null;
    }

    public function findByUserIdWithRelations(string $userId): array
    {
        return AccountModel::query()
            ->with(['bank', 'currency'])
            ->where('user_id', $userId)
            ->orderBy('order')
            ->get()
            ->all();
    }

    public function findByIdWithRelations(string $id): ?AccountModel
    {
        return AccountModel::query()
            ->with(['bank', 'currency'])
            ->where('id', $id)
            ->get()
            ->first();
    }
}

