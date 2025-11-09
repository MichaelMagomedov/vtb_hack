<?php

declare(strict_types=1);

namespace App\Ai\Repositories\Load\Impl;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Entities\LoadEntity;
use App\Ai\Enums\LoadTypeEnum;
use App\Ai\Models\LoadModel;
use App\Ai\Repositories\Load\LoadRepository;
use App\Root\Mappers\EloquentEntityMapper;
use DateTime;
use Illuminate\Support\Facades\DB;

final class LoadDatabaseRepositoryImpl implements LoadRepository
{
    private EloquentEntityMapper $eloquentEntityMapper;

    public function __construct(EloquentEntityMapper $eloquentEntityMapper) {
        $this->eloquentEntityMapper = $eloquentEntityMapper;
    }

    public function save(LoadEntity $entity): LoadEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, LoadModel::class);
        $model->saveOrFail();

        return $this->eloquentEntityMapper->toEntity($model, LoadEntity::class);
    }

    public function update(LoadEntity $entity, array $attributes = []): LoadEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, LoadModel::class);
        $model->exists = true;
        $model->updateOrFail($attributes);

        return $this->eloquentEntityMapper->toEntity($model, LoadEntity::class);
    }

    public function updateSpecificAttributes(LoadEntity $entity, array $attributes = []): LoadEntity {
        $model = $this->eloquentEntityMapper->toModel($entity, LoadModel::class);
        $newValues = [];
        foreach ($attributes as $attribute) {
            $newValues[$attribute] = $model->$attribute;
        }
        LoadModel::where('id', $entity->getId())->update($newValues);
        $model = LoadModel::query()->where('id', $entity->getId())->first();
        return $this->eloquentEntityMapper->toEntity($model, LoadEntity::class);
    }

    public function findById(string $id): ?LoadEntity {
        $model = LoadModel::query()->where('id', $id)->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, LoadEntity::class);
    }

    public function findUserLoadStack(string $userId, LoadTypeEnum $type, string $accountId = null): array {

        $from = (new DateTime())->modify('-10 minutes');
        $builder = LoadModel::query()
            ->where('user_id', $userId)
            ->whereIn('status', array_map(fn(LoadStatusEnum $s) => $s->value, LoadStatusEnum::IN_PROCESS))
            ->where('type', $type->value)
            // берем только те, которые не "зависли" <- а считаем зависшими только те
            // которые выполняются уже больше 10 минут (их потом крончик заверишит)
            ->where('created_at', '>=', $from->format('Y-m-d H:i:s'));

        if ($accountId !== null) {
            $builder = $builder->where('account_id', $accountId);
        }

        return $this->eloquentEntityMapper->toEntityArray($builder->orderBy('created_at')->get(), LoadEntity::class);
    }


    public function findCountSuccessLoadByPrevHour(string $accountId): int {
        $startTime = (new DateTime())->setTime((int)date('H'), 0, 0);
        $endTime = (new DateTime())->setTime((int)date('H'), 59, 59);

        $result = DB::selectOne("
            SELECT count(*) as count
            FROM loads
            WHERE created_at >= :startTime
              AND created_at <= :endTime
              AND status = :status
              AND account_id = :account_id
       ", [
            ':startTime' => $startTime->format('Y-m-d H:i'),
            ':endTime' => $endTime->format('Y-m-d H:i'),
            ':status' => LoadStatusEnum::SUCCESS->value,
            ':account_id' => $accountId,
        ]);

        return (int)$result->count;
    }

    public function findPrevSuccessLoad(string $id, string $userId, LoadTypeEnum $type): ?LoadEntity {
        $model = LoadModel::query()
            ->whereNotIn('id', [$id])
            ->where('status', LoadStatusEnum::SUCCESS->value)
            ->where('user_id', $userId)
            ->where('type', $type->value)
            ->orderBy('created_at', 'DESC')
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, LoadEntity::class);
    }

    public function findPrevSuccessLoadByAccount(string $id, string $accountId): ?LoadEntity {
        $model = LoadModel::query()
            ->whereNotIn('id', [$id])
            ->where('status', LoadStatusEnum::SUCCESS->value)
            ->where('account_id', $accountId)
            ->where('type', LoadTypeEnum::PARSE_TRANSACTION->value)
            ->orderBy('created_at', 'DESC')
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, LoadEntity::class);
    }

    public function findHungLoads(DateTime $from, array $statuses, int $limit = 1000): array {
        $builder = LoadModel::query()
            ->whereIn('status', array_map(fn(LoadStatusEnum $s) => $s->value, $statuses))
            ->where('created_at', '<=', $from->format('Y-m-d H:i:s'));

        return $this->eloquentEntityMapper->toEntityArray($builder->get(), LoadEntity::class);
    }

    public function addInputCharsCount(string $id, int $count): void {
        DB::table('loads')->where('id', $id)->update([
            'input_chars_count' => DB::raw('input_chars_count + ' . $count),
        ]);
    }

    public function addInputWordsCount(string $id, int $count): void {
        DB::table('loads')->where('id', $id)->update([
            'input_words_count' => DB::raw('input_words_count + ' . $count),
        ]);
    }

    public function addOutputCharsCount(string $id, int $count): void {
        DB::table('loads')->where('id', $id)->update([
            'output_chars_count' => DB::raw('output_chars_count + ' . $count),
        ]);
    }

    public function addOutputWordsCount(string $id, int $count): void {
        DB::table('loads')->where('id', $id)->update([
            'output_words_count' => DB::raw('output_words_count + ' . $count),
        ]);
    }
}

