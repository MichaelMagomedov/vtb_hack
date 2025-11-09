<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Exceptions;

use App\Root\Exceptions\RuntimeUserFriendlyLoggableException;
use Psr\Log\LogLevel;

final class ParseOperationsNotFoundException extends RuntimeUserFriendlyLoggableException
{
    protected string $level = LogLevel::WARNING;

    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::transaction.job_exceptions.save_operations_exception');
    }

    protected function getLoggingReason(): string {
        return 'Логировать нужно что бы посмотреть почему chatgpt не нашел операции';
    }
}
