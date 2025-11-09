<?php

declare(strict_types=1);

namespace App\Ai\Exceptions;

use App\Root\Exceptions\RuntimeUserFriendlyLoggableException;

final class AiAssistantMaxAttemptException extends RuntimeUserFriendlyLoggableException
{
    protected function getLoggingReason(): string {
        return 'По каким то причинам истекли попытки на получение ответа от chatGpt';
    }

    protected function getTranslateMessage(): string {
        return trans('ai::ai.chatgpt_unknown_exception');
    }
}
