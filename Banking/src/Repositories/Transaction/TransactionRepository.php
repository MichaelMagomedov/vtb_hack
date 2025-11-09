<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Transaction;

use App\Banking\Entities\TransactionEntity;
use DateTime;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface TransactionRepository
{
    public function save(TransactionEntity $entity): TransactionEntity;

    public function update(TransactionEntity $entity): TransactionEntity;

    public function delete(string $id): void;

    public function deleteByLoadId(string $loadId): void;

    public function restoreByLoadId(string $loadId): void;

    public function setVerified(
        string    $userId,
        ?string   $id = null,
        ?string   $categoryId = null,
        ?bool     $allowEmptyCategory = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?bool     $excludeIncome = false,
        ?bool     $excludeExpense = false,
    ): void;

    public function deleteByAccountAndDate(string $accountId, DateTime $dateTime, string $excludeLoadId): void;

    public function findSimilarByAmountAndDate(string $userId, float $amount, DateTime $dateTime, string $excludeAccountId, string $operationCode = null): ?TransactionEntity;

    public function findDateUntilWhichParseNotAvailable(string $accountId, string $excludeLoadId): ?DateTime;

    public function findById(string $id): ?TransactionEntity;

    public function belongsUser(string $id, string $userId): bool;

    public function findSumByDays(string $userId, DateTime $startTime, DateTime $endTime): array;

    public function findUserIncomes(string $userId, DateTime $startTime, DateTime $endTime): array;

    public function findUserExpenses(string $userId, DateTime $startTime, DateTime $endTime): array;

    public function findLastTransaction(string $userId): ?TransactionEntity;

    /** @return TransactionEntity[] */
    public function findByIds(array $ids): array;

    public function findMaxOrder(string $userId, DateTime $dateTime): ?int;

    public function updateAllByDestination(
        string   $userId,
        string   $destination,
        ?string  $categoryId = null,
        ?string  $codeId = null,
        DateTime $from,
        DateTime $to,
    ): void;

}

