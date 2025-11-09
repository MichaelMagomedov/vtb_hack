<?php

declare(strict_types=1);

namespace App\Banking\Enums;

enum TransactionTypeEnum: string
{
    case SIMPLE = 'simple';
    case SBP = 'sbp';
    case BETWEEN_ACCOUNTS = 'between_accounts';

    case HOLD = 'hold';

    public function label(): string
    {
        return match($this) {
            static::SIMPLE => 'Обычная покупка или обычная операцию',
            static::SBP => 'Система быстрых переводов СБП',
            static::BETWEEN_ACCOUNTS => 'Перевод между своими счетами',
            static::HOLD => 'Неподтвержденная операция',
        };
    }
}
