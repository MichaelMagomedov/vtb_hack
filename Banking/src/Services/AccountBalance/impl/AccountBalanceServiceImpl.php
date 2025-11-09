<?php

declare(strict_types=1);

namespace App\Banking\Services\AccountBalance\impl;

use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\LoadService;
use App\Banking\Entities\AccountBalanceEntity;
use App\Banking\Events\AccountBalanceChangeEvent;
use App\Banking\Repositories\AccountBalance\AccountBalanceRepository;
use App\Banking\Services\AccountBalance\AccountBalanceService;
use App\Banking\Services\AccountBalance\Dto\CreateAccountBalanceDto;
use App\Banking\Services\AccountBalance\Dto\UpdateAccountBalanceDto;
use App\Recommendation\Services\UserRecommendationsSettings\UserRecommendationsSettingsService;
use Illuminate\Database\ConnectionInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

class AccountBalanceServiceImpl implements AccountBalanceService
{
    public function __construct(
        private readonly ConnectionInterface                $connection,
        private readonly LoadService                        $loadService,
        private readonly LoadRepository                     $loadRepository,
        private readonly AccountBalanceRepository           $accountBalanceRepository,
        private readonly UserRecommendationsSettingsService $userRecommendationsSettingsService,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function createAfterParse(array $accountBalances): AccountBalanceEntity
    {
        try {
            $this->connection->beginTransaction();
            $totalBalanceForDate = 0;
            foreach ($accountBalances as $accountBalanceData) {
                $accountBalance = $this->create($accountBalanceData);
                $totalBalanceForDate += $accountBalance->getBalance();
            }
            // удаляем все корректировки баланса за переданную дату
            // намерено удаляем даже корректировки для конкретных аккаунтов
            // так как они сохранялись просто для логов
            $this->accountBalanceRepository->deleteByDate(
                $accountBalances[0]->getUserId(),
                $accountBalances[0]->getBalanceDate()
            );
            $totalAccountBalance = $this->create(new CreateAccountBalanceDto(
                $totalBalanceForDate,
                $accountBalances[0]->getBalanceDate(),
                $accountBalances[0]->getUserId(),
                null,
                $accountBalances[0]->getLoadId()
            ));
            $this->connection->commit();
            return $totalAccountBalance;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function create(CreateAccountBalanceDto $createData): AccountBalanceEntity
    {
        try {
            // так как на одну дату могут быть только один баланс
            $this->accountBalanceRepository->deleteByDate(
                $createData->getUserId(),
                $createData->getBalanceDate(),
            );
            $order = $this->accountBalanceRepository->findMaxOrder($createData->getUserId());
            $order = $order ? $order + 1 : 1;
            $accountBalance = $this->accountBalanceRepository->save(new AccountBalanceEntity(
                Uuid::uuid4()->toString(),
                $createData->getBalance(),
                $createData->getBalanceDate(),
                $order,
                $createData->getUserId(),
                $createData->getAccountId(),
                $createData->getLoadId()
            ));
            event(new AccountBalanceChangeEvent($accountBalance));
            $this->connection->commit();
            return $accountBalance;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function delete(string $id): void
    {
        $accountBalance = $this->accountBalanceRepository->findById($id);
        $this->accountBalanceRepository->delete($id);
        event(new AccountBalanceChangeEvent($accountBalance));
    }

    public function deleteAllBalancesIfAndRestoreOld(string $loadId): void
    {
        try {
            $this->connection->beginTransaction();
            $load = $this->loadRepository->findById($loadId);
            $this->accountBalanceRepository->deleteByLoadId($loadId);
            // если это последняя загрузка то ресторим предыдущую
            // а если есть в очереди другие загрузки, то мы просто чистим за собой.
            // а другие загрузки уже сгенерируют новые данные
            if ($this->loadService->isLastInProcessLoadInStack($loadId)) {
                // см StatisticsTransactionRepository::deleteByAccountAndDate() - по этому тут используем account id
                $prevLoad = $this->loadRepository->findPrevSuccessLoadByAccount(
                    $load->getId(),
                    $load->getAccountId()
                );
                if ($prevLoad !== null) {
                    // восстанавливаем старые транзакции, которые удалили перед началом парсинга
                    $this->accountBalanceRepository->restoreByLoadId($prevLoad->getId());
                }
            }
            // небольшой костылек делаем не через event а на прямую
            $this->userRecommendationsSettingsService->startCalculateChartData($load->getUserId());
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function updateOrder(array $ids): void
    {
        try {
            $this->connection->beginTransaction();
            $accountBalances = $this->accountBalanceRepository->findByIds($ids);
            /**
             * переворачиваем порядок, что бы шло от большего к меньшему
             * что бы корректно добавлять в верх при создании @see AccountBalanceRepository::findMaxOrder()
             */
            $ids = array_reverse($ids);
            foreach ($accountBalances as $accountBalance) {
                foreach ($ids as $order => $id) {
                    if ($accountBalance->getId() === $id) {
                        $accountBalance = $accountBalance->withOrder($order);
                        $this->accountBalanceRepository->update($accountBalance);
                    }
                }
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function update(UpdateAccountBalanceDto $updateData): AccountBalanceEntity
    {
        try {
            $this->connection->beginTransaction();
            $accountBalance = $this->accountBalanceRepository->findById($updateData->getId());
            // так как на одну дату могут быть только один баланс
            // вдруг уже ранее были на переданнуб дату балансы
            // мы их удаляем кроме текущего баланса
            $this->accountBalanceRepository->deleteByDate(
                $accountBalance->getUserId(),
                $updateData->getBalanceDate(),
                $accountBalance->getId()
            );
            $accountBalance = $accountBalance
                ->withBalanceDate($updateData->getBalanceDate())
                ->withBalance($updateData->getBalance());
            $accountBalance = $this->accountBalanceRepository->update($accountBalance);
            $this->connection->commit();

            event(new AccountBalanceChangeEvent($accountBalance));
            return $accountBalance;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}
