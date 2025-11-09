<?php

declare(strict_types=1);

namespace App\Ai\Services\Load\Exceptions;

use App\Root\Enums\UserFriendlyErrorCodeEnum;
use App\Root\Exceptions\UserFriendlyException;

final class LoadExampleException extends UserFriendlyException
{
    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string
    {
        return trans('banking::load.exception');
    }

    /**
     * @inheritDoc
     */
    protected function getErrorCode(): int
    {
        return UserFriendlyErrorCodeEnum::EXAMPLE->value;
    }
}
