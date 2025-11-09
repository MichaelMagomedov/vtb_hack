<?php

declare(strict_types=1);

namespace App\Banking\Services\Transaction;

use App\Banking\Entities\TransactionEntity;
use App\Banking\Services\Transaction\Dto\CreateTransactionDto;
use App\Banking\Services\Transaction\Dto\UpdateTransactionDto;
use App\Banking\Services\Transaction\Exceptions\TransactionNotFoundException;
use DateTime;

interface TransactionService
{
    /**
     * @param CreateTransactionDto[] $transactions
     * @return TransactionEntity[]
     */
    public function saveTransactionsAfterParse(array $transactions, string $loadId): array;

    public function save(CreateTransactionDto $transactionData, bool $useUserPatterns = false, bool $changeEvent = true): ?TransactionEntity;

    /**
     * @throws  TransactionNotFoundException
     */
    public function update(UpdateTransactionDto $updateData): TransactionEntity;

    public function delete(string $id): void;

    public function updateOrder(array $ids): void;

    public function verify(
        string    $userId,
        ?string   $id,
        ?string   $categoryId,
        ?bool     $allowEmptyCategory = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?bool     $excludeIncome = false,
        ?bool     $excludeExpense = false,
    ): void;

    public function deleteAllTransactionsIfAndRestoreOld(string $loadId): void;
}
