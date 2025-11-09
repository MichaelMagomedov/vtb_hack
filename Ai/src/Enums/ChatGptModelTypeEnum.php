<?php

declare(strict_types=1);

namespace App\Ai\Enums;

enum ChatGptModelTypeEnum: string
{
    // использовать для чувствительных мест
    // TODO ЕЁ НЕ ИСПОЛЬЗОВАТЬ!!! - оооочень дорого ()
    case GPT_4O = 'gpt-4o';

    // использовать во всех остальных местах
    case GPT_4O_MINI = 'gpt-4o-mini';
}
