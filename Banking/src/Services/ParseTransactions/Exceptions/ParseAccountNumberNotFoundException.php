<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Exceptions;

use App\Root\Exceptions\UserFriendlyException;

final class ParseAccountNumberNotFoundException extends UserFriendlyException
{
    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::transaction.job_exceptions.save_account_number_exception');
    }
}
