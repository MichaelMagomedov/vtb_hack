<?php

declare(strict_types=1);

namespace App\Banking\Services\UserTransactionPattern\Impl;

use App\Banking\Entities\TransactionEntity;
use App\Banking\Entities\UserTransactionPatternEntity;
use App\Banking\Repositories\Account\AccountRepository;
use App\Banking\Repositories\Transaction\TransactionRepository;
use App\Banking\Repositories\UserTransactionPattern\UserTransactionPatternRepository;
use App\Banking\Services\Transaction\Dto\UpdateTransactionPatternDto;
use App\Banking\Services\UserTransactionPattern\UserTransactionPatternService;
use DateTime;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

class UserTransactionPatternServiceImpl implements UserTransactionPatternService
{
    public function __construct(
        private readonly ConnectionInterface              $connection,
        private readonly AccountRepository                $accountRepository,
        private readonly TransactionRepository            $transactionRepository,
        private readonly UserTransactionPatternRepository $transactionPatternRepository
    )
    {
    }

    public function update(UpdateTransactionPatternDto $patternParams, TransactionEntity $transaction): ?UserTransactionPatternEntity
    {
        try {
            $this->connection->beginTransaction();

            // все равно мы не сможем не обновить текущий паттерн не создать какой либо другой
            if (empty($patternParams->getId()) && empty($transaction->getDestination())) {
                $this->connection->commit();
                return null;
            }

            // пытаемся найти по id
            $pattern = null;
            if (!empty($patternParams->getId())) {
                $pattern = $this->transactionPatternRepository->findById($patternParams->getId());
            }

            $userAccount = $this->accountRepository->findById($transaction->getAccountId());
            if ($pattern === null && !empty($transaction->getDestination())) {
                $pattern = $this->transactionPatternRepository->findByUserIdAndDestination(
                    $userAccount->getUserId(),
                    $transaction->getDestination()
                );
            }

            // если все же паттерн не нашли то пытаемся его создать
            if ($pattern === null) {
                $pattern = $this->transactionPatternRepository->save(new UserTransactionPatternEntity(
                    Uuid::uuid4()->toString(),
                    mb_strtolower($transaction->getDestination()),
                    $userAccount->getUserId(),
                    $transaction->getId(),
                    $patternParams->getCategoryId(),
                    $patternParams->getCodeId()
                ));
                // если нашли паттерн то обновляем его
            } else {
                $pattern = $this->transactionPatternRepository->update(
                    $pattern
                        ->withFromTransactionId($transaction->getId())
                        ->withCategoryId($patternParams->getCategoryId())
                        ->withCodeId($patternParams->getCodeId())
                );
            }
            // обновляем данные за +- 3 месяца
            $this->transactionRepository->updateAllByDestination(
                $pattern->getUserId(),
                $pattern->getDestination(),
                $pattern->getCategoryId(),
                $pattern->getCodeId(),
                (clone $transaction->getDate())->modify('-3 month'),
                (clone $transaction->getDate())->modify('+3 month'),
            );

            $this->connection->commit();
            return $pattern;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function deleteByTransaction(TransactionEntity $transaction): void
    {
        if (empty($transaction->getDestination())) {
            return;
        }
        $userAccount = $this->accountRepository->findById($transaction->getAccountId());
        $existPattern = $this->transactionPatternRepository->findByUserIdAndDestination(
            $userAccount->getUserId(),
            $transaction->getDestination()
        );
        if ($existPattern !== null) {
            $this->transactionPatternRepository->delete($existPattern->getId());
        }
    }
}

