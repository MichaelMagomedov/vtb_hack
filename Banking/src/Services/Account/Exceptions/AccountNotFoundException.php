<?php

declare(strict_types=1);

namespace App\Banking\Services\Account\Exceptions;

use App\Root\Exceptions\UserFriendlyException;

final class AccountNotFoundException extends UserFriendlyException
{
    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::account.not_found_exception');
    }
}
