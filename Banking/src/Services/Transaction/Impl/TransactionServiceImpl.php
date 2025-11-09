<?php

declare(strict_types=1);

namespace App\Banking\Services\Transaction\Impl;

use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\LoadService;
use App\Banking\Entities\TransactionEntity;
use App\Banking\Enums\AccountTypeEnum;
use App\Banking\Enums\TransactionTypeEnum;
use App\Banking\Events\TransactionChangeEvent;
use App\Banking\Repositories\Account\AccountRepository;
use App\Banking\Repositories\Transaction\Impl\TransactionDatabaseRepositoryImpl;
use App\Banking\Repositories\Transaction\TransactionRepository;
use App\Banking\Repositories\TransactionCode\TransactionCodeRepository;
use App\Banking\Repositories\UserTransactionPattern\UserTransactionPatternRepository;
use App\Banking\Services\Transaction\Dto\CreateTransactionDto;
use App\Banking\Services\Transaction\Dto\UpdateTransactionDto;
use App\Banking\Services\Transaction\Exceptions\TransactionNotFoundException;
use App\Banking\Services\Transaction\TransactionService;
use App\Banking\Services\UserTransactionPattern\UserTransactionPatternService;
use DateTime;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

final class TransactionServiceImpl implements TransactionService
{
    const POPULAR_MARKETS = [
        'wildberries',
        'ozon',
        'lamoda',
        'купер',
        'aliexpress'
    ];

    public function __construct(
        private readonly LoadService                      $loadService,
        private readonly LoadRepository                   $loadRepository,
        private readonly ConnectionInterface              $connection,
        private readonly AccountRepository                $accountRepository,
        private readonly TransactionRepository            $transactionRepository,
        private readonly TransactionCodeRepository        $transactionCodeRepository,
        private readonly UserTransactionPatternService    $userTransactionPatternService,
        private readonly UserTransactionPatternRepository $userTransactionPatternRepository
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function saveTransactionsAfterParse(array $transactions, string $loadId): array
    {
        try {
            $this->connection->beginTransaction();
            // не даем обновлять операции тех дней, за которые мы уже делали парсинг (такие дни считаем законченными)
            // сделано так что бы постоянно не пересохранять уже распаршеные операции
            $dateUntilWhichParseNotAvailable = null;
            foreach ($transactions as $index => $transactionData) {
                if ($dateUntilWhichParseNotAvailable === null) {
                    $dateUntilWhichParseNotAvailable = $this->transactionRepository->findDateUntilWhichParseNotAvailable(
                        $transactionData->getAccountId(),
                        $loadId
                    );
                }
                $date = clone $transactionData->getDate();
                $date = $date->setTime(12, 0, 0);
                if ($dateUntilWhichParseNotAvailable !== null && $date <= $dateUntilWhichParseNotAvailable) {
                    unset($transactions[$index]);
                }
            }

            // - сначала удаляем все ранее загруженные транзакции для аккаунта и дня
            //   (важно сначала просто отчистить предыдущие загрузки а только потом  уже сохранять новые)
            //
            // - loadId используется так как все транзакции загружаются партиями через очереди
            //   и не нужно удалять транзакции из соседней job (но удаляем все старые)
            foreach ($transactions as $transactionData) {
                $this->transactionRepository->deleteByAccountAndDate(
                    $transactionData->getAccountId(),
                    $transactionData->getDate(),
                    $loadId
                );
            }

            $account = null;
            $newTransactions = [];
            foreach ($transactions as $index => $transactionData) {
                $transactionData = $transactionData->withOrder($index);
                $transaction = $this->save($transactionData, true, false, true);

                if ($account === null) {
                    $account = $this->accountRepository->findById($transaction->getAccountId());
                }

                // считаем что на кредитке нет пополняющих операцию кроме погашения долгов (перевод между аккаунтами)
                // это нужно что бы отфильтровать транзакции вид: "Предоставление транша для кредита"
                // обычно такое любит писать альфа банк на каждую операцию по кредитке
                // НЕ можем это сделать раньше так как в методе save мы можем изменить тип операции на BETWEEN_ACCOUNTS
                // если найдем похожу. в других счетах (на ту же сумму)
                if ($account->getType() === AccountTypeEnum::CREDIT &&
                    $transaction->getAmount() > 0 &&
                    $transaction->getType() !== TransactionTypeEnum::BETWEEN_ACCOUNTS
                ) {
                    // сразу дроавем
                    $this->transactionRepository->delete($transaction->getId());
                    continue;
                }

                $newTransactions[] = $transaction;
            }

            $this->connection->commit();
            return $newTransactions;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function save(
        CreateTransactionDto $transactionData,
        bool                 $useUserPatterns = false,
        bool                 $changeEvent = true,
        bool                 $findSimilar = true
    ): TransactionEntity
    {
        try {
            $this->connection->beginTransaction();
            $order = $transactionData->getOrder();
            $account = $this->accountRepository->findById($transactionData->getAccountId());
            if ($order === null) {
                $order = $this->transactionRepository->findMaxOrder($account->getUserId(), $transactionData->getDate()) ?? 0;
                $order++;
            }
            // просто костыль так как задолбались)_
            $mcc = $transactionData->getMcc();
            foreach (self::POPULAR_MARKETS as $market) {
                if (str_contains(mb_strtolower($transactionData->getShortDestination()), mb_strtolower($market))) {
                    $mcc = 5262;
                }
                if (!empty($transactionData->getDescription()) && str_contains(mb_strtolower($transactionData->getDescription()), mb_strtolower($market))) {
                    $mcc = 5262;
                }
            }
            // сначала ищем по mcc коду
            $categoryCodeId = $transactionData->getCategoryId();
            if ($categoryCodeId === null && $mcc !== null) {
                $codeEntity = $this->transactionCodeRepository->findByCode($mcc);
                $categoryCodeId = $codeEntity?->getCategoryId();
            }
            // потом категорию ищем по id кода
            if ($transactionData->getCodeId() !== null) {
                $codeEntity = $this->transactionCodeRepository->findById($transactionData->getCodeId());
                $categoryCodeId = $codeEntity?->getCategoryId();
            }

            // пытаемся найти mcc по коду
            $codeId = $transactionData->getCodeId();
            if ($codeId === null && $mcc !== null) {
                $codeEntity = $this->transactionCodeRepository->findByCode($mcc);
                $codeId = $codeEntity?->getId();
            }
            // ищем среди других счетов похожие транзакции вдруг это перевод между счетами
            // так как переводов обычно много и по дефолту мы их скрываем
            $operationCode = $transactionData->getOperationCode();
            $transactionType = $transactionData->getType();
            $similarTransaction = $this->transactionRepository->findSimilarByAmountAndDate(
                $account->getUserId(),
                -1 * $transactionData->getAmount(),
                $transactionData->getDate(),
                $transactionData->getAccountId(),
                $transactionData->getOperationCode()
            );
            if ($findSimilar === true && $transactionType === TransactionTypeEnum::SIMPLE && $similarTransaction !== null) {
                $operationCode = $transactionData->getOperationCode() ?? $similarTransaction->getOperationCode();
                $transactionType = TransactionTypeEnum::BETWEEN_ACCOUNTS;
                $similarTransaction = $similarTransaction
                    ->withOperationCode($operationCode)
                    ->withType(TransactionTypeEnum::BETWEEN_ACCOUNTS);
                $this->transactionRepository->update($similarTransaction);
            }

            // по дефолту переводу между счетами считаем проверенными
            $verified = false;
            if ($transactionData->getType() === TransactionTypeEnum::BETWEEN_ACCOUNTS) {
                $verified = true;
            }

            // пытаемся вытащить телефон при sbp платеже
            $destination = $transactionData->getDestination();
            if ($transactionData->getType() === TransactionTypeEnum::SBP &&
                empty($destination) &&
                !empty($transactionData->getDescription())
            ) {
                $phonePattern = '~(?:\+7|8)\s?\(?\d{3}\)?\s?\d{3}[-\s]?\d{2}[-\s]?\d{2}~';
                if (preg_match($phonePattern, $transactionData->getDescription(), $match)) {
                    $destination = $match[0];
                    $destination = preg_replace('/\D/', '', $destination);
                }
            }

            // пытаемся найти категорию по уже проверенным пользователем транзакциям
            // когда у пользователя прожата галка применить категорию ко всем транзакциям с "таким получаетелем"
            if ($useUserPatterns === true && !empty($destination)) {
                $userPattern = $this->userTransactionPatternRepository->findByUserIdAndDestination(
                    $account->getUserId(),
                    $destination
                );
                if ($userPattern !== null) {
                    $categoryCodeId = $userPattern->getCategoryId();
                    $codeId = $userPattern->getCodeId();
                    $verified = true;
                }
            }

            $transactionEntity = $this->transactionRepository->save(new TransactionEntity(
                Uuid::uuid4()->toString(),
                $transactionData->getAccountId(),
                $transactionData->getAmount(),
                $transactionData->getDate(),
                $transactionType,
                $order,
                $transactionData->getShortDestination(),
                $transactionData->getLoadId(),
                $operationCode,
                $transactionData->getDescription(),
                $destination,
                $mcc,
                $categoryCodeId,
                $codeId,
                $transactionData->getColor(),
                $account->getUserId(),
                $transactionData->getMccReason(),
                $verified
            ));
            if ($changeEvent === true) {
                event(new TransactionChangeEvent($transactionEntity));
            }
            $this->connection->commit();
            return $transactionEntity;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function update(UpdateTransactionDto $updateData): TransactionEntity
    {
        try {
            $this->connection->beginTransaction();
            $oldTransaction  = $this->transactionRepository->findById($updateData->getId());
            if ($oldTransaction  === null) {
                throw new TransactionNotFoundException();
            }
            $transaction = $oldTransaction
                ->withAmount($updateData->getAmount())
                ->withAccountId($updateData->getAccountId())
                ->withDate($updateData->getDate())
                ->withType($updateData->getType())
                ->withDesc($updateData->getDesc())
                ->withCodeId($updateData->getCodeId())
                ->withShortDesc($updateData->getShortDesc())
                ->withDestination($updateData->getDestination())
                ->withCategoryId($updateData->getCategoryId())
                ->withColor($updateData->getColor());
            $transaction = $this->transactionRepository->update($transaction);
            if ($oldTransaction->getAmount() <> $transaction->getAmount() ||
                $oldTransaction->getDate() <> $transaction->getDate()
            ) {
                event(new TransactionChangeEvent($transaction));
            }
            // если мы передали параметры создания паттерна
            // то создаем его
            if ($updateData->getPatternParams() !== null) {
                $this->userTransactionPatternService->update($updateData->getPatternParams(), $transaction);

                // если галка не прожата, то удаляем параметр
            } elseif ($transaction->getDestination() !== null) {
                $this->userTransactionPatternService->deleteByTransaction($transaction);
            }

            $this->connection->commit();
            return $transaction;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function delete(string $id): void
    {
        $transaction = $this->transactionRepository->findById($id);
        $this->transactionRepository->delete($id);
        event(new TransactionChangeEvent($transaction));
    }

    public function updateOrder(array $ids): void
    {
        try {
            $this->connection->beginTransaction();
            $transactions = $this->transactionRepository->findByIds($ids);
            /**
             * переворачиваем порядок, что бы шло от большего к меньшему
             * что бы корректно добавлять в верх при создании @see TransactionDatabaseRepositoryImpl::findMaxOrder()
             */
            $ids = array_reverse($ids);
            foreach ($transactions as $transaction) {
                foreach ($ids as $order => $id) {
                    if ($transaction->getId() === $id) {
                        $transaction = $transaction->withOrder($order);
                        $this->transactionRepository->update($transaction);
                    }
                }
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function verify(
        string    $userId,
        ?string   $id,
        ?string   $categoryId,
        ?bool     $allowEmptyCategory = null,
        ?DateTime $from = null,
        ?DateTime $to = null,
        ?bool     $excludeIncome = false,
        ?bool     $excludeExpense = false,
    ): void
    {
        $this->transactionRepository->setVerified(
            $userId,
            $id,
            $categoryId, $allowEmptyCategory,
            $from,
            $to,
            $excludeIncome,
            $excludeExpense
        );
    }

    public function deleteAllTransactionsIfAndRestoreOld(string $loadId): void
    {
        try {
            $this->connection->beginTransaction();
            $this->transactionRepository->deleteByLoadId($loadId);
            // если это последняя загрузка то ресторим предыдущую
            // а если есть в очереди другие загрузки, то мы просто чистим за собой и другие загрузки
            // уже сгенерируют новые данные
            if ($this->loadService->isLastInProcessLoadInStack($loadId)) {
                $load = $this->loadRepository->findById($loadId);
                // см StatisticsTransactionRepository::deleteByAccountAndDate() - по этому тут используем account id
                $prevLoad = $this->loadRepository->findPrevSuccessLoadByAccount(
                    $load->getId(),
                    $load->getAccountId()
                );
                if ($prevLoad !== null) {
                    // восстанавливаем старые транзакции, которые удалили перед началом парсинга
                    $this->transactionRepository->restoreByLoadId($prevLoad->getId());
                }
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}
