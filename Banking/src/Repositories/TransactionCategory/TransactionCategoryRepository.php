<?php

declare(strict_types=1);

namespace App\Banking\Repositories\TransactionCategory;

use App\Banking\Entities\TransactionCategoryEntity;

/**
 * этот вид репозиториев используется только в сервисах
 * в контроллерах использовать view repository
 */
interface TransactionCategoryRepository
{
    /**
     * @return  TransactionCategoryEntity[]
     */
    public function findAll(): array;

    public function findById(string $id): ?TransactionCategoryEntity;

    public function save(TransactionCategoryEntity $entity): TransactionCategoryEntity;
}

