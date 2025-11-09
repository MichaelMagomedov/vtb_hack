<?php

declare(strict_types=1);

namespace App\Banking\Repositories\TransactionCode\Impl;

use App\Banking\Entities\TransactionCodeEntity;
use App\Banking\Models\TransactionCodeModel;
use App\Banking\Repositories\TransactionCode\TransactionCodeRepository;
use App\Banking\Repositories\TransactionCode\TransactionCodeViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionCodeDatabaseRepositoryImpl implements TransactionCodeRepository, TransactionCodeViewRepository
{
    private EloquentEntityMapper $eloquentEntityMapper;

    public function __construct(EloquentEntityMapper $eloquentEntityMapper) {
        $this->eloquentEntityMapper = $eloquentEntityMapper;
    }

    /** @InheritDoc */
    public function findAll(): array {
        $models = TransactionCodeModel::query()->get();
        return $this->eloquentEntityMapper->toEntityArray($models, TransactionCodeEntity::class);
    }

    /** @InheritDoc */
    public function save(TransactionCodeEntity $entity): TransactionCodeEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, TransactionCodeModel::class);
        $model->saveOrFail();
        return $this->eloquentEntityMapper->toEntity($model, TransactionCodeEntity::class);
    }

    /** @InheritDoc */
    public function findByCode(int $code): ?TransactionCodeEntity {
        $consumer = TransactionCodeModel::query()->where('code', $code)->first();
        if ($consumer === null) {
            return null;
        }
        return $this->eloquentEntityMapper->toEntity($consumer, TransactionCodeEntity::class);

    }

    /** @InheritDoc */
    public function findById(string $id): ?TransactionCodeEntity {
        $consumer = TransactionCodeModel::query()->where('id', $id)->first();
        if ($consumer === null) {
            return null;
        }
        return $this->eloquentEntityMapper->toEntity($consumer, TransactionCodeEntity::class);
    }

    public function findAllWithRelations(?string $categoryId, ?string $query = null): LengthAwarePaginator {
        $query = $query ? mb_strtolower($query) : null;
        $builder = TransactionCodeModel::query()
            ->with(['category']);
        if ($categoryId !== null) {
            $builder->whereHas('category', function (Builder $builder) use ($categoryId) {
                $builder->where('id', $categoryId);
            });
        }
        if ($query !== null) {
            $builder->where(function (Builder $builder) use ($query) {
                $builder
                    ->orWhereRaw(DB::raw("lower(name) like '%$query%'"))
                    ->orWhereRaw(DB::raw("lower(\"desc\") like '%$query%'"))
                    ->whereHas('category', function (Builder $builder) use ($query) {
                        $builder->orWhereRaw(DB::raw("lower(name) like '%$query%'"));
                    });
            });
        }

        return $builder->paginate();
    }
}
