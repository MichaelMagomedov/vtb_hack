<?php

declare(strict_types=1);

namespace App\Banking\Services\Account\Impl;

use App\Banking\Entities\AccountEntity;
use App\Banking\Enums\CurrencyEnum;
use App\Banking\Events\AccountChangeEvent;
use App\Banking\Repositories\Account\AccountRepository;
use App\Banking\Repositories\Bank\BankRepository;
use App\Banking\Repositories\Currency\CurrencyRepository;
use App\Banking\Services\Account\AccountService;
use App\Banking\Services\Account\Dto\GetOrCreateAccountDto;
use App\Banking\Services\Account\Dto\UpdateAccountDto;
use App\Banking\Services\Account\Exceptions\AccountNotFoundException;
use App\Banking\Services\Account\Exceptions\AccountWithNumberAlreadyExistsException;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

final class AccountServiceImpl implements AccountService
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly LoggerInterface     $logger,
        private readonly BankRepository      $bankRepository,
        private readonly CurrencyRepository  $currencyRepository,
        private readonly AccountRepository   $accountRepository
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getOrCreate(GetOrCreateAccountDto $accountData): AccountEntity
    {
        $existingAccount = $this->accountRepository->findByNumber(
            $accountData->getUserId(),
            $accountData->getNumber(),
        );
        $bank = null;
        if ($accountData->getBankId() !== null) {
            $bank = $this->bankRepository->findById($accountData->getBankId());
            if ($bank === null) {
                $this->logger->error('Chat gpt вернул не верный id банка нужно срочно добавлять дополнительный поиск по alias ' . $accountData->getBankId());
            }
        }
        if ($existingAccount !== null) {
            // если есть какие-то не установленные данные. то обновляем их
            $existingAccount = $existingAccount
                ->withType($existingAccount->getType() ?? $accountData->getType())
                ->withName($existingAccount->getName() ?? $accountData->getName());
            if (empty($existingAccount->getBankId()) && $bank !== null) {
                $existingAccount = $existingAccount
                    ->withBankId($bank->getId())
                    ->withBankReason($accountData->getBankReason());
            }
            if (empty($existingAccount->getCurrencyId())) {
                $existingAccount = $existingAccount
                    ->withCurrencyId($accountData->getCurrencyId())
                    ->withCurrencyReason($accountData->getCurrencyReason());
            }

            $existingAccount = $this->accountRepository->update($existingAccount);
            return $existingAccount;
        }
        $currency = null;
        if ($accountData->getCurrencyId() !== null) {
            $currency = $this->currencyRepository->findById($accountData->getCurrencyId());
        }
        $defaultCurrency = $this->currencyRepository->findByCode(CurrencyEnum::RUB);
        $order = $this->accountRepository->findMaxOrder($accountData->getUserId()) ?? 0;
        $order += 1;
        $name = $accountData->getName();
        if ($name === null) {
            $name = trans('banking::account.number_label', ['order' => $order]);
        }

        $account = $this->accountRepository->save(new AccountEntity(
            Uuid::uuid4()->toString(),
            $accountData->getUserId(),
            $accountData->getNumber(),
            $name,
            $order,
            $accountData->getType(),
            $currency ? $currency->getId() : $defaultCurrency->getId(),
            ($bank !== null ? $bank->getId() : null),
            $accountData->getBankReason(),
            $accountData->getCurrencyReason(),
        ));
        return $account;

    }

    /**
     * @inheritDoc
     */
    public function update(UpdateAccountDto $updateData): AccountEntity
    {
        $account = $this->accountRepository->findById($updateData->getId());
        if ($account === null) {
            throw new AccountNotFoundException();
        }
        $existsAccountWithNumber = $this->accountRepository->findByNumber(
            $account->getUserId(),
            (string)$updateData->getNumber(),
            $account->getBankId()
        );
        if ($existsAccountWithNumber !== null) {
            throw new AccountWithNumberAlreadyExistsException();
        }
        $account = $account
            ->withName($updateData->getName())
            ->withType($updateData->getType())
            ->withCurrencyId($updateData->getCurrencyId())
            ->withNumber((string)$updateData->getNumber())
            ->withBankId($updateData->getBankId());

        return $this->accountRepository->update($account);
    }

    public function delete(string $id): void
    {
        $account = $this->accountRepository->findById($id);
        event(new AccountChangeEvent($account));
        $this->accountRepository->delete($id);
    }

    public function updateOrder(array $ids): void
    {
        try {
            $this->connection->beginTransaction();
            $accounts = $this->accountRepository->findByIds($ids);
            foreach ($accounts as $account) {
                foreach ($ids as $order => $id) {
                    if ($account->getId() === $id) {
                        $account = $account->withOrder($order);
                        $this->accountRepository->update($account);
                    }
                }
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}
