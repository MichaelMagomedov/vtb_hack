<?php

declare(strict_types=1);

namespace App\Banking\Services\Account;

use App\Banking\Entities\AccountEntity;
use App\Banking\Services\Account\Dto\GetOrCreateAccountDto;
use App\Banking\Services\Account\Dto\UpdateAccountDto;
use App\Banking\Services\Account\Exceptions\AccountNotFoundException;
use App\Banking\Services\Account\Exceptions\AccountWithNumberAlreadyExistsException;

interface AccountService
{
    public function getOrCreate(GetOrCreateAccountDto $accountData): AccountEntity;

    /**
     * @throws AccountNotFoundException
     * @throws AccountWithNumberAlreadyExistsException
     */
    public function update(UpdateAccountDto $updateData): AccountEntity;

    public function delete(string $id): void;

    public function updateOrder(array $ids): void;
}
