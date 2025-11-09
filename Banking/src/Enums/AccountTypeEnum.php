<?php

declare(strict_types=1);

namespace App\Banking\Enums;

enum AccountTypeEnum: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';


    public function label(): string
    {
        return match($this) {
            static::DEBIT => 'Обычный дебетовый счет',
            static::CREDIT => 'Кредитный счет',
        };
    }
}
