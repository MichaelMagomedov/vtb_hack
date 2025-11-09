<?php

declare(strict_types=1);

namespace App\Ai\Exceptions;

use App\Root\Exceptions\RuntimeUserFriendlyLoggableException;

final class AiAssistantRequestException extends RuntimeUserFriendlyLoggableException
{
    protected function getLoggingReason(): string {
        return 'По каким то причинам ai отдал непонятную ошибку и в каждом случае нужно разбираться что было не так';
    }

    protected function getTranslateMessage(): string {
        return trans('ai::ai.chatgpt_unknown_exception');
    }
}
