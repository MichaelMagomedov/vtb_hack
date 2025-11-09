<?php

declare(strict_types=1);

namespace App\Ai\Repositories\AiPrompt;

use App\Ai\Entities\LoadEntity;
use App\Ai\Jobs\AfterAssistantRunJob\AfterAssistantRunnable;
use Illuminate\Foundation\Bus\PendingChain;

/**
 * В этом репозитории мы составляем инструкции команды для асистента
 *
 * так как запросы к ассистенту занимаю время, мы их кидаем в очередь и только потом выполняем действие
 * Возвращает подготовленную очередь заданий которую ты можешь запускать и объединять как угодно
 */
interface AiPromptRepository
{
    /**
     * @deprecated
     *
     *  ВНИМАНИЕ! Этот метод не использует ассистента так что тут используется getCompletionJsonResponse
     *  для использования file_search и использование ассистента см использование функции @getAssistantJsonResponse
     */
    public function distributeMccCodes(array $codes, array $categories): array;

    public function prepareTransactionsDataAndRunAction(string $pdfText, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain;

    public function prepareAccountDataAndRunAction(string $pdfText, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain;

    public function prepareIncomesAndRunAction(array $incomes, array $months, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain;

    public function prepareMonthlyExpensesAndRunAction(array $expenses, array $months, LoadEntity $load, AfterAssistantRunnable $afterJob): PendingChain;
}
