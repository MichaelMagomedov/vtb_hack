<?php

declare(strict_types=1);

namespace App\Ai\Services\Load\Dto;

use App\Ai\Entities\LoadEntity;
use App\Ai\Enums\LoadTypeEnum;
use App\Banking\Entities\AccountEntity;

final class StopAnotherLoadDto
{
    public function __construct(
        private readonly string        $userId,
        private readonly LoadTypeEnum  $type,
        private readonly LoadEntity    $excludeLoad,
        private readonly ?AccountEntity $account = null
    ) {
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getType(): LoadTypeEnum {
        return $this->type;
    }

    public function getExcludeLoad(): LoadEntity {
        return $this->excludeLoad;
    }

    public function getAccount(): ?AccountEntity {
        return $this->account;
    }
}
