<?php

declare(strict_types=1);

namespace App\Ai\Enums;

enum LoadTypeEnum: string
{
    case PARSE_TRANSACTION = 'parse_transaction';
    case GENERATE_EXPENSES_TEMPLATE = 'generate_expenses_template';
}
