<?php

declare(strict_types=1);

namespace App\Banking\Repositories\TransactionCategory\Impl;

use App\Banking\Entities\TransactionCategoryEntity;
use App\Banking\Models\TransactionCategoryModel;
use App\Banking\Repositories\TransactionCategory\TransactionCategoryRepository;
use App\Banking\Repositories\TransactionCategory\TransactionCategoryViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final class TransactionCategoryDatabaseRepositoryImpl implements TransactionCategoryRepository, TransactionCategoryViewRepository
{
    private EloquentEntityMapper $eloquentEntityMapper;

    public function __construct(EloquentEntityMapper $eloquentEntityMapper) {
        $this->eloquentEntityMapper = $eloquentEntityMapper;
    }

    /** @inheritDoc */
    public function findAll(): array {
        $models = TransactionCategoryModel::query()->get();
        return $this->eloquentEntityMapper->toEntityArray($models, TransactionCategoryEntity::class);
    }

    public function findById(string $id): ?TransactionCategoryEntity {
        $category = TransactionCategoryModel::query()->where('id', $id)->first();
        if ($category === null) {
            return null;
        }
        return $this->eloquentEntityMapper->toEntity($category, TransactionCategoryEntity::class);
    }

    /** @inheritDoc */
    public function save(TransactionCategoryEntity $entity): TransactionCategoryEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, TransactionCategoryModel::class);
        $model->saveOrFail();
        return $this->eloquentEntityMapper->toEntity($model, TransactionCategoryEntity::class);
    }

    public function findAllWithRelations(string $userId, ?string $codeId = null, ?string $query = null): LengthAwarePaginator {
        $builder = TransactionCategoryModel::query()
            ->selectRaw(DB::raw('transaction_categories.*, count(distinct transactions.id) as transactions_count'))
            ->leftJoin('transactions', function (JoinClause $join) use ($userId) {
                $join->on('transaction_categories.id', '=', 'transactions.category_id');
                $join->where('transactions.user_id', '=', $userId);
                $join->whereNull('transactions.deleted_at');
            })
            ->groupBy('transaction_categories.id')
            ->orderByRaw(DB::raw("transactions_count DESC, transaction_categories.order ASC"));

        if ($codeId != null) {
            $builder->whereHas('codes', function (Builder $builder) use ($codeId) {
                $builder->where('id', $codeId);
            });
        }
        if ($query !== null) {
            $query = mb_strtolower(trim($query));
            $builder->orWhereRaw(DB::raw("trim(lower(name)) like '%$query%'"));
        }

        return $builder->paginate();
    }


    public function findAllByUser(string $userId, DateTime $from, DateTime $to): array {
        $from = (clone $from)->setTime(0, 0);
        $to = (clone $to)->setTime(23, 59, 59);

        return TransactionCategoryModel::query()
            ->whereHas('transactions', function (Builder $builder) use ($userId, $from, $to) {
                $builder
                    ->whereDate('date', '>=', $from)
                    ->whereDate('date', '<=', $to);
                $builder->whereHas('account', function (Builder $builder) use ($userId) {
                    $builder->where('user_id', $userId);
                });
            })
            ->orderBy('order')
            ->get()
            ->all();
    }
}

