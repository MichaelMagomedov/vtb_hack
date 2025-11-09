<?php

declare(strict_types=1);

namespace App\Banking\Repositories\Transaction;

use App\Banking\Models\TransactionModel;
use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Этот вид репозиториев используется для контроллеров
 * что бы уметь отдавать модели вместе с зависимостями
 * ведь зависимости нельзя получить из entity
 */
interface TransactionViewRepository
{
    public function findByUserIdWithRelations(
        string    $userId,
        ?string   $query = null,
        ?DateTime $startTime = null,
        ?DateTime $endTime = null,
        ?array    $excludeTypes = null,
        ?bool     $excludeIncome = null,
        ?string   $categoryId = null,
        ?bool   $allowEmptyCategory = null,
        ?bool   $onlyNotVerified = null,
        ?bool   $excludeExpense = null
    ): LengthAwarePaginator;

    public function findByIdWithRelations(string $id): ?TransactionModel;

    public function findDestinationAutocomplete(string $query, string $userId): array;
}

