<?php

declare(strict_types=1);

namespace App\Banking\Repositories\TransactionCode;

use App\Banking\Entities\TransactionCodeEntity;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface TransactionCodeRepository
{
    /**
     * @return  TransactionCodeEntity[]
     */
    public function findAll(): array;

    public function findById(string $id): ?TransactionCodeEntity;

    public function findByCode(int $code): ?TransactionCodeEntity;

    public function save(TransactionCodeEntity $entity): TransactionCodeEntity;
}

