<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Transaction\Impl;

use App\Banking\Entities\TransactionEntity;
use App\Banking\Enums\AccountTypeEnum;
use App\Banking\Enums\TransactionTypeEnum;
use App\Banking\Models\TransactionModel;
use App\Banking\Repositories\Transaction\TransactionRepository;
use App\Banking\Repositories\Transaction\TransactionViewRepository;
use App\Root\Mappers\EloquentEntityMapper;
use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionDatabaseRepositoryImpl implements TransactionRepository, TransactionViewRepository
{
    public function __construct(
        private readonly EloquentEntityMapper $eloquentEntityMapper
    )
    {
    }

    public function save(TransactionEntity $entity): TransactionEntity
    {
        $model = $this->eloquentEntityMapper->toModel($entity, TransactionModel::class);
        $model->saveOrFail();

        return $this->eloquentEntityMapper->toEntity($model, TransactionEntity::class);
    }

    public function update(TransactionEntity $entity): TransactionEntity
    {
        $model = $this->eloquentEntityMapper->toModel($entity, TransactionModel::class);
        $model->exists = true;
        $model->updateOrFail();

        return $this->eloquentEntityMapper->toEntity($model, TransactionEntity::class);
    }

    public function delete(string $id): void
    {
        TransactionModel::where('id', $id)->delete();
    }

    public function deleteByLoadId(string $loadId): void
    {
        TransactionModel::where('load_id', $loadId)->delete();
    }

    public function restoreByLoadId(string $loadId): void
    {
        TransactionModel::withTrashed()->where('load_id', $loadId)->restore();
    }

    public function setVerified(
        string    $userId,
        ?string   $id = null,
        ?string   $categoryId = null,
        ?bool     $allowEmptyCategory = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?bool     $excludeIncome = false,
        ?bool     $excludeExpense = false,
    ): void
    {
        $builder = TransactionModel::query()->where('user_id', $userId);
        if ($id !== null) {
            $builder = $builder->where('id', $id);
        }
        if ($categoryId !== null) {
            $builder = $builder->where('category_id', $categoryId);
        }
        if ($allowEmptyCategory !== null && $allowEmptyCategory && $categoryId === null) {
            $builder->whereNull('category_id');
        }
        if ($from !== null) {
            $from = (clone $from)->setTime(0, 0);
            $builder->whereDate('date', '>=', $from);
        }
        if ($to !== null) {
            $to = (clone $to)->setTime(23, 59, 59);
            $builder->whereDate('date', '<=', $to);
        }
        if ($excludeIncome !== null && $excludeIncome) {
            $builder->where('amount', '<=', 0);
        }
        if ($excludeExpense !== null && $excludeExpense) {
            // считаем что на кредитке нет пополняющих операциюй кроме погашения долгов
            // это нужно что бы отфильтровать транзакции вид: "Предоставление транша для кредита"
            // обычно такое любит писать альфабанк на каждую операцию по кредитке
            $builder
                ->where('amount', '>=', 0)
                ->whereHas('account', function (Builder $builder) {
                    $builder->whereNotIn('type', [AccountTypeEnum::CREDIT->value]);
                });
        }

        $builder->update(['verified' => true]);
    }

    public function deleteByAccountAndDate(string $accountId, DateTime $dateTime, string $excludeLoadId): void
    {
        $startDate = (clone $dateTime)->setTime(0, 0);
        $endDate = (clone $dateTime)->setTime(23, 59, 59);
        TransactionModel::query()
            ->where('account_id', $accountId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereNotIn('load_id', [$excludeLoadId])
            ->delete();
    }

    public function findSimilarByAmountAndDate(string $userId, float $amount, DateTime $dateTime, string $excludeAccountId, string $operationCode = null): ?TransactionEntity
    {
        $startTime = (clone $dateTime)->modify('-1 second');
        $endTime = (clone $dateTime)->modify('+1 second');

        $builder = TransactionModel::query()
            ->where(function (Builder $builder) use ($startTime, $endTime, $amount, $operationCode) {
                $builder->orWhere(function (Builder $builder) use ($startTime, $endTime, $amount) {
                    $builder
                        ->where('date', '>=', $startTime->format('Y-m-d H:i:s'))
                        ->where('date', '<=', $endTime->format('Y-m-d H:i:s'))
                        ->where('amount', $amount);
                });
                if ($operationCode !== null) {
                    $builder->orWhere('operation_code', $operationCode);
                }
            })
            // ищем похожие транзакции только cреди не удаленных аккаунтов пользователя
            ->whereHas('account', function (Builder $builder) use ($userId) {
                $builder
                    ->withTrashed()
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at');
            })
            ->whereNotIn('account_id', [$excludeAccountId]);

        $sql = getSqlQuery($builder);
        // для дебага
        $model = $builder->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, TransactionEntity::class);

    }

    /**
     * Берем последнюю транзакцию не из этой загрузки t.load_id <> :load_id  (так как в рамках одной загрузки
     * парралельно загружается много транзакций) и только для этого счета что бы можно было прогрузить один и тот же
     * день для нескольких  счетов
     */
    public function findDateUntilWhichParseNotAvailable(string $accountId, string $excludeLoadId): ?DateTime
    {
        $result = DB::select("
            SELECT DISTINCT date::date as date
            FROM transactions t
            WHERE account_id = :account_id
                AND t.deleted_at is null
                AND t.load_id <> :load_id
            ORDER BY date DESC
            LIMIT 1
       ", [
            ':account_id' => $accountId,
            ':load_id' => $excludeLoadId,
        ]);
        //
        if (count($result) === 0) {
            return null;
        }
        return (new DateTime($result[0]->date))->setTime(23, 59, 59);
    }

    public function findById(string $id): ?TransactionEntity
    {
        $model = TransactionModel::query()
            ->where('id', $id)
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, TransactionEntity::class);
    }


    public function findByIds(array $ids): array
    {
        $transactionModels = TransactionModel::query()->whereIn('id', $ids)->get();

        return $this->eloquentEntityMapper->toEntityArray($transactionModels, TransactionEntity::class);

    }

    public function updateAllByDestination(
        string   $userId,
        string   $destination,
        ?string  $categoryId = null,
        ?string  $codeId = null,
        DateTime $from,
        DateTime $to,
    ): void
    {
        $from = (clone $from)->setTime(0, 0);
        $to = (clone $to)->setTime(23, 59, 59);

        $destination = mb_strtolower($destination);
        TransactionModel::query()
            ->whereRaw(DB::raw("lower(destination) = lower('$destination')"))
            ->whereHas('account', function (Builder $builder) use ($userId) {
                $builder
                    ->withTrashed()
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at');
            })
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            // НЕ УБИРАЕМ ПРОСТАВЛЯЕМ категорию только не проверенным транзакциям
            // что бы не нечаянно ничего лишнего не обновить
            // а если транзакция не проверена то пользователь её проверят и убедится что авто проставление сработало верно
            ->where('verified', false)
            ->update([
                'category_id' => $categoryId,
                'code_id' => $codeId,
            ]);
    }

    public function findMaxOrder(string $userId, DateTime $dateTime): ?int
    {
        $startTime = (clone $dateTime)->setTime(0, 0, 0);
        $endTime = (clone $dateTime)->setTime(23, 59, 59);

        $result = DB::selectOne("
            SELECT MAX(t.order) as max_order FROM transactions t
            INNER JOIN accounts as a on t.account_id = a.id
            WHERE a.user_id = :user_id
            AND a.deleted_at IS NULL
            AND t.deleted_at is null
            AND t.date >= :start_date
            AND t.date <= :end_date
        ", [
            ':user_id' => $userId,
            ':start_date' => $startTime->format('Y-m-d H:i'),
            ':end_date' => $endTime->format('Y-m-d H:i'),
        ]);

        return $result->max_order ? (int)$result->max_order : null;
    }

    public function belongsUser(string $id, string $userId): bool
    {
        return TransactionModel::query()
            ->where('id', $id)
            ->whereHas('account', function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })->exists();
    }

    public function findSumByDays(string $userId, DateTime $startTime, DateTime $endTime): array
    {
        return TransactionModel::query()
            ->selectRaw('DATE(date) as date, SUM(amount) as total_amount')
            ->whereHas('account', function (Builder $builder) use ($userId) {
                $builder
                    ->withTrashed()
                    ->whereNull('deleted_at');
            })
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $startTime)
            ->whereDate('date', '<=', $endTime)
            ->groupByRaw('DATE(date)')
            ->orderBy('date', 'ASC')
            ->pluck('total_amount', 'date') // ключи — даты, значения — суммы
            ->toArray();
    }

    public function findUserIncomes(string $userId, DateTime $startTime, DateTime $endTime): array
    {
        $builder = $this->getIncomesOrExpensesQuery($userId, $startTime, $endTime);
        $transactionModels = $builder
            ->where('amount', '>=', 0)
            ->whereHas('account', function (Builder $builder) use ($userId) {
                // считаем что на кредитке нет пополняющих операциюй кроме погашения долгов
                // это нужно что бы отфильтровать транзакции вид: "Предоставление транша для кредита"
                // обычно такое любит писать альфабанк на каждую операцию по кредитке
                $builder->whereNotIn('type', [AccountTypeEnum::CREDIT->value]);
            });

        return $this->eloquentEntityMapper->toEntityArray($transactionModels->get(), TransactionEntity::class);
    }

    public function findUserExpenses(string $userId, DateTime $startTime, DateTime $endTime): array
    {
        $builder = $this->getIncomesOrExpensesQuery($userId, $startTime, $endTime);
        $transactionModels = $builder
            ->where('amount', '<', 0)
            ->get();

        return $this->eloquentEntityMapper->toEntityArray($transactionModels, TransactionEntity::class);
    }

    public function findLastTransaction(string $userId): ?TransactionEntity
    {
        $model = TransactionModel::query()
            ->whereHas('account', function (Builder $builder) {
                // ищем те транзакции, у которых аккаунт не удален
                $builder
                    ->withTrashed()
                    ->whereNull('deleted_at');
            })
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->first();
        if ($model === null) {
            return null;
        }

        return $this->eloquentEntityMapper->toEntity($model, TransactionEntity::class);
    }

    public function findByUserIdWithRelations(
        string    $userId,
        ?string   $query = null,
        ?DateTime $startTime = null,
        ?DateTime $endTime = null,
        ?array    $excludeTypes = null,
        ?bool     $excludeIncome = null,
        ?string   $categoryId = null,
        ?bool     $allowEmptyCategory = null,
        ?bool     $onlyNotVerified = null,
        ?bool     $excludeExpense = null
    ): LengthAwarePaginator
    {
        $query = $query ? mb_strtolower($query) : null;
        $transactionQuery = TransactionModel::query()
            ->with(['category', 'code', 'account', 'account.currency'])
            ->whereHas('account', function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            });
        if ($query !== null) {
            /** qu */
            $transactionQuery->where(function (Builder $builder) use ($query) {
                $builder
                    ->orWhereRaw(DB::raw("lower(\"desc\") like '%" . $query . "%'"))
                    ->orWhereRaw(DB::raw("lower('short_desc') like '%" . $query . "%'"))
                    ->orWhereRaw(DB::raw("lower('destination') like '%" . $query . "%'"));
            });
        }
        if ($startTime !== null) {
            $transactionQuery->whereDate('date', '>=', $startTime);
        }
        if ($endTime !== null) {
            $transactionQuery->whereDate('date', '<=', $endTime);
        }
        if ($excludeTypes !== null) {
            $transactionQuery->whereNotIn('type', $excludeTypes);
        }
        if ($excludeIncome !== null && $excludeIncome) {
            $transactionQuery->where('amount', '<=', 0);
        }
        if ($excludeExpense !== null && $excludeExpense) {
            // считаем что на кредитке нет пополняющих операциюй кроме погашения долгов
            // это нужно что бы отфильтровать транзакции вид: "Предоставление транша для кредита"
            // обычно такое любит писать альфабанк на каждую операцию по кредитке
            $transactionQuery
                ->where('amount', '>=', 0)
                ->whereHas('account', function (Builder $builder) {
                    $builder->whereNotIn('type', [AccountTypeEnum::CREDIT->value]);
                });
        }
        if ($categoryId !== null) {
            $transactionQuery->where('category_id', $categoryId);
        }
        // это сочетание allow empty category и не переданная
        // категория то тогда в sql ставим category = null
        if ($allowEmptyCategory !== null && $allowEmptyCategory && $categoryId === null) {
            $transactionQuery->whereNull('category_id');
        }
        if ($onlyNotVerified !== null && $onlyNotVerified === true) {
            $transactionQuery->where('verified', false);
        }
        return $transactionQuery
            ->orderByRaw('DATE(date) DESC')
            ->orderBy('order', 'DESC')
            // очень важно что бы всегда сохранялся одинаковый порядок
            // если по какой то причине будет несколько одинаковых order то
            // paginate может их дублировать так как mysql будет делать их в рандом порядке
            // и offset тогда сломается
            ->orderBy('id')
            ->paginate();
    }

    public function findByIdWithRelations(string $id): ?TransactionModel
    {
        return TransactionModel::query()
            ->with([
                'category',
                'code',
                'account',
                'account.currency'
            ])
            ->where('id', $id)
            ->get()
            ->first();
    }

    public function findDestinationAutocomplete(string $query, string $userId): array
    {
        if (strlen($query) === 0) {
            return [];
        }

        return TransactionModel::withRowNumber('destination')
            ->with(['category', 'code'])
            ->select(['destination', 'code_id', 'category_id', 'type', 'short_desc', 'desc', 'color'])
            ->where('row_number', 1)
            ->whereHas('account', function (Builder $builder) use ($userId) {
                $builder->where('user_id', $userId);
            })
            ->whereRaw(DB::raw("lower(\"destination\") like '%" . mb_strtolower($query) . "%'"))
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()
            ->all();

    }

    private function getIncomesOrExpensesQuery(string $userId, DateTime $startTime, DateTime $endTime): Builder
    {
        $startTime = (clone $startTime)->setTime(0, 0);
        $endTime = (clone $endTime)->setTime(23, 59, 59);

        return TransactionModel::query()
            ->whereHas('account', function (Builder $builder) use ($userId) {
                $builder
                    ->withTrashed()
                    ->whereNull('deleted_at');
            })
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $startTime)
            ->whereDate('date', '<=', $endTime)
            // исключаем переводы между счетами
            ->whereNotIn('type', [TransactionTypeEnum::BETWEEN_ACCOUNTS->value])
            ->orderBy('date', 'DESC');
    }
}

