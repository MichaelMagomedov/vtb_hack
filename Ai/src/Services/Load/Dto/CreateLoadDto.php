<?php

declare(strict_types=1);

namespace App\Ai\Services\Load\Dto;

use App\Ai\Enums\LoadTypeEnum;

final class CreateLoadDto
{
    public function __construct(
        private readonly string  $userId,
        private readonly ?string $chatId = null,
        private readonly ?LoadTypeEnum $loadTypeEnum = LoadTypeEnum::PARSE_TRANSACTION
    ) {
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getChatId(): ?string {
        return $this->chatId;
    }

    public function getLoadTypeEnum(): ?LoadTypeEnum {
        return $this->loadTypeEnum;
    }
}
