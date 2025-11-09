<?php

declare(strict_types=1);

// тут указана папка где берем конфиги для сущнсотей модуля
use App\Ai\Enums\ChatGptModelTypeEnum;

return [
    'key' => env('GPT_KEY', ''),
    'vector_store_id' => env('GPT_VECTOR_STORE_ID', ''),
    'assistant_id' => env('GPT_ASSISTANT_ID', ''),
    'model' => env('GPT_MODEL', ChatGptModelTypeEnum::GPT_4O_MINI->value),
];
