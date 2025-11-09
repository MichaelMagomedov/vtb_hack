<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Bank\Impl;

use App\Banking\Entities\BankEntity;
use App\Banking\Models\BankModel;
use App\Banking\Repositories\Bank\BankRepository;
use App\Banking\Repositories\Bank\BankViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class BankDatabaseRepositoryImpl implements BankRepository, BankViewRepository
{
    public function __construct(
        private readonly EloquentEntityMapper $eloquentEntityMapper
    ) {
    }

    public function findById(string $id): ?BankEntity {
        $model = BankModel::query()
            ->where('id', $id)
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, BankEntity::class);

    }

    public function findAllWithRelations(?string $query = null): LengthAwarePaginator {
        $query = $query ? mb_strtolower($query) : null;
        $bankQuery = BankModel::query();
        if ($query !== null) {
            $bankQuery->where(function (Builder $builder) use ($query) {
                $builder
                    ->orWhereRaw(DB::raw("lower(name) like '%$query%'"))
                    ->orWhereRaw(DB::raw("lower(alias) like '%$query%'"));
            });
        }
        return $bankQuery->paginate();
    }
}
