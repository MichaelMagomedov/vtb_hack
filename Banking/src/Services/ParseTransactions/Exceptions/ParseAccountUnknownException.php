<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Exceptions;

use App\Root\Exceptions\UserFriendlyException;

final class ParseAccountUnknownException extends UserFriendlyException
{
    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::transaction.job_exceptions.save_account_data_unknown_exception');
    }
}
