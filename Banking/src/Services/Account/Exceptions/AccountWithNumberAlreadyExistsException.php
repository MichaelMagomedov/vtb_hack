<?php

declare(strict_types=1);

namespace App\Banking\Services\Account\Exceptions;

use App\Root\Exceptions\UserFriendlyException;

final class AccountWithNumberAlreadyExistsException extends UserFriendlyException
{
    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::account.with_number_already_exists');
    }
}
