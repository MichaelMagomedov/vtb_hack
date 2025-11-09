<?php

declare(strict_types=1);

namespace App\Banking\Services\Transaction\Exceptions;

use App\Root\Exceptions\UserFriendlyException;

final class TransactionNotFoundException extends UserFriendlyException
{
    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::transaction.not_found_exception');
    }
}
