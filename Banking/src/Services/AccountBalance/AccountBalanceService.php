<?php

declare(strict_types=1);

namespace App\Banking\Services\AccountBalance;

use App\Banking\Entities\AccountBalanceEntity;
use App\Banking\Services\AccountBalance\Dto\CreateAccountBalanceDto;
use App\Banking\Services\AccountBalance\Dto\UpdateAccountBalanceDto;

interface AccountBalanceService
{
    public function create(CreateAccountBalanceDto $createData): AccountBalanceEntity;

    /**
     * @param CreateAccountBalanceDto[] $accountBalances
     */
    public function createAfterParse(array $accountBalances): AccountBalanceEntity;

    public function update(UpdateAccountBalanceDto $updateData): AccountBalanceEntity;

    public function delete(string $id): void;

    public function deleteAllBalancesIfAndRestoreOld(string $loadId): void;

    public function updateOrder(array $ids): void;
}
