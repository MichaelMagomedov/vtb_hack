<?php

declare(strict_types=1);

namespace App\Ai\Enums;

enum ChatGptRunStatusEnum: string
{
    case CREATED = 'created';
    case QUEUED = 'queued';
    case IN_PROGRESS = 'in_progress';
    case REQUIRES_ACTION = 'requires_action';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELING = 'cancelling';
    case CANCELED = 'cancelled';
    case EXPIRED = 'expired';

    public static function inProcess(): array {
        return [
            ChatGptRunStatusEnum::CREATED,
            ChatGptRunStatusEnum::QUEUED,
            ChatGptRunStatusEnum::IN_PROGRESS,
        ];
    }

    public static function fail(): array {
        return [
            ChatGptRunStatusEnum::CANCELING,
            ChatGptRunStatusEnum::CANCELED,
            ChatGptRunStatusEnum::EXPIRED,
            ChatGptRunStatusEnum::FAILED

        ];
    }

    public static function complete(): array {
        return [
            ChatGptRunStatusEnum::COMPLETED,
            ChatGptRunStatusEnum::REQUIRES_ACTION,
        ];
    }
}
