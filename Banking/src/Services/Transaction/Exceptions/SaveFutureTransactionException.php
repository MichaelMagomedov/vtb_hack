<?php

declare(strict_types=1);

namespace App\Banking\Services\Transaction\Exceptions;

use App\Root\Exceptions\UserFriendlyException;

final class SaveFutureTransactionException extends UserFriendlyException
{
    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::transaction.save_future_operation');
    }
}
