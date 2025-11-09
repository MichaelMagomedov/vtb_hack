<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Exceptions;

use App\Root\Exceptions\RuntimeUserFriendlyLoggableException;
use Psr\Log\LogLevel;

final class ParseTransactionFileExtensionException extends RuntimeUserFriendlyLoggableException
{
    protected string $level = LogLevel::WARNING;

    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::transaction.job_exceptions.parse_transactions_extension_exception');
    }

    protected function getLoggingReason(): string {
        return 'Такие случае мы должны отбривать на этапе когда файлы заливают в бота';
    }
}
