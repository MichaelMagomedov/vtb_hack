<?php

declare(strict_types=1);

namespace App\Banking\Repositories\AccountBalance\Impl;

use App\Banking\Entities\AccountBalanceEntity;
use App\Banking\Models\AccountBalanceModel;
use App\Banking\Repositories\AccountBalance\AccountBalanceRepository;
use App\Banking\Repositories\AccountBalance\AccountBalanceViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class AccountBalanceDatabaseRepositoryImpl implements AccountBalanceRepository, AccountBalanceViewRepository
{
    private EloquentEntityMapper $eloquentEntityMapper;

    public function __construct(EloquentEntityMapper $eloquentEntityMapper)
    {
        $this->eloquentEntityMapper = $eloquentEntityMapper;
    }

    public function findById(string $id): ?AccountBalanceEntity
    {
        $model = AccountBalanceModel::query()
            ->where('id', $id)
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, AccountBalanceEntity::class);
    }

    public function save(AccountBalanceEntity $entity): AccountBalanceEntity
    {
        $model = $this->eloquentEntityMapper->toModel($entity, AccountBalanceModel::class);
        $model->saveOrFail();

        return $this->eloquentEntityMapper->toEntity($model, AccountBalanceEntity::class);
    }

    public function update(AccountBalanceEntity $entity): AccountBalanceEntity
    {
        $model = $this->eloquentEntityMapper->toModel($entity, AccountBalanceModel::class);
        $model->exists = true;
        $model->updateOrFail();

        return $this->eloquentEntityMapper->toEntity($model, AccountBalanceEntity::class);
    }

    public function belongsUser(string $id, string $userId): bool
    {
        return AccountBalanceModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->exists();
    }

    public function findByUser(string $userId, DateTime $from, DateTime $to): array
    {
        $from = $from->setTime(0, 0, 0);
        $to = $to->setTime(23, 59, 59);
        $accountBalancesModels = AccountBalanceModel::query()
            ->where('user_id', $userId)
            ->whereDate('balance_date', '>=', $from)
            ->whereDate('balance_date', '<=', $to)
            ->orderBy('order', 'DESC')
            ->get();

        return $this->eloquentEntityMapper->toEntityArray($accountBalancesModels, AccountBalanceEntity::class);
    }

    public function getFirstAccountBalance(string $userId, DateTime $to): ?AccountBalanceEntity
    {
        $model = AccountBalanceModel::query()
            ->where('user_id', $userId)
            ->whereDate('balance_date', '<=', $to)
            ->orderBy('balance_date', 'ASC')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, AccountBalanceEntity::class);
    }

    public function getLastAccountBalance(string $userId, DateTime $to): ?AccountBalanceEntity
    {
        $model = AccountBalanceModel::query()
            ->where('user_id', $userId)
            ->whereDate('balance_date', '<=', $to)
            ->orderBy('balance_date', 'DESC')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, AccountBalanceEntity::class);
    }

    public function delete(string $id): void
    {
        AccountBalanceModel::where('id', $id)->delete();
    }


    public function deleteByLoadId(string $loadId): void
    {
        AccountBalanceModel::where('load_id', $loadId)->delete();
    }

    public function restoreByLoadId(string $loadId): void
    {
        AccountBalanceModel::withTrashed()->where('load_id', $loadId)->restore();
    }

    public function findMaxOrder(string $userId): ?int
    {
        $result = DB::selectOne("
            SELECT MAX(t.order) as max_order
            FROM account_balances t
            WHERE t.user_id = :user_id
            AND t.deleted_at IS NULL
        ", [
            ':user_id' => $userId,
        ]);

        return $result->max_order ? (int)$result->max_order : null;
    }

    public function deleteByDate(string $userId, DateTime $balanceDate, string $excludeId = null): void
    {
        $startDate = (clone $balanceDate)->setTime(0, 0);
        $endDate = (clone $balanceDate)->setTime(23, 59, 59);
        $builder = AccountBalanceModel::query()
            ->where('user_id', $userId)
            ->whereDate('balance_date', '>=', $startDate)
            ->whereDate('balance_date', '<=', $endDate);

        if ($excludeId !== null) {
            $builder->whereNotIn('id', [$excludeId]);
        }

        $builder->delete();
    }

    public function findByIds(array $ids): array
    {
        $accountBalancesModels = AccountBalanceModel::query()->whereIn('id', $ids)->get();

        return $this->eloquentEntityMapper->toEntityArray($accountBalancesModels, AccountBalanceEntity::class);
    }

    public function findByUserIdWithRelations(string $userId, ?string $accountId = null, DateTime $from = null, DateTime $to = null): LengthAwarePaginator
    {
        $builder = AccountBalanceModel::query()
            ->where('user_id', $userId);

        if ($accountId !== null) {
            $builder->where('account_id', '=', $accountId);
        }

        return $builder
            ->orderBy('order', 'DESC')
            // очень важно что бы всегда сохранялся одинаковый порядок
            // если по какой то причине будет несколько одинаковых order то
            // paginate может их дублировать так как mysql будет делать их в рандом порядке
            // и offset тогда сломается
            ->orderBy('id')
            ->paginate();
    }

    public function findAllByUserIdWithRelations(string $userId, DateTime $from = null, DateTime $to = null): array
    {
        $builder = AccountBalanceModel::query()
            ->where('user_id', $userId);

        if ($from !== null) {
            $from = $from->setTime(0, 0, 0);
            $builder->whereDate('balance_date', '>=', $from);
        }
        if ($to !== null) {
            $to = $to->setTime(23, 59, 59);
            $builder->whereDate('balance_date', '<=', $to);
        }

        return $builder
            ->orderBy('order', 'DESC')
            ->get()
            ->all();
    }

    public function findByIdWithRelations(string $id): ?AccountBalanceModel
    {
        return AccountBalanceModel::query()
            ->with(['account', 'account.bank'])
            ->where('id', $id)
            ->get()
            ->first();
    }
}

