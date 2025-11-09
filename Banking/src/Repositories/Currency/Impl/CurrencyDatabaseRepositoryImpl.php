<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Currency\Impl;

use App\Banking\Entities\CurrencyEntity;
use App\Banking\Enums\CurrencyEnum;
use App\Banking\Models\CurrencyModel;
use App\Banking\Repositories\Currency\CurrencyRepository;
use App\Banking\Repositories\Currency\CurrencyViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use Illuminate\Support\Facades\DB;

final class CurrencyDatabaseRepositoryImpl implements CurrencyRepository, CurrencyViewRepository
{
    private EloquentEntityMapper $eloquentEntityMapper;

    public function __construct(EloquentEntityMapper $eloquentEntityMapper) {
        $this->eloquentEntityMapper = $eloquentEntityMapper;
    }

    public function findById(string $id): ?CurrencyEntity {
        $model = CurrencyModel::query()->where('id', $id)->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, CurrencyEntity::class);
    }

    public function findByCode(CurrencyEnum $currencyEnum): ?CurrencyEntity {
        $code = mb_strtolower($currencyEnum->value);
        $model = CurrencyModel::query()
            ->whereRaw(DB::raw("lower(code) = '$code'"))
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, CurrencyEntity::class);
    }

    public function findAllWithRelations(): array {
        return CurrencyModel::query()
            ->get()
            ->all();
    }
}

