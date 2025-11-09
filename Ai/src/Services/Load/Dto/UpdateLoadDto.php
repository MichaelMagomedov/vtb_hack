<?php

declare(strict_types=1);

namespace App\Ai\Services\Load\Dto;

use App\Ai\Enums\LoadStatusEnum;

final class UpdateLoadDto
{
    public function __construct(
        private readonly string         $id,
        private readonly LoadStatusEnum $status,
        private readonly ?string        $accountId
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getAccountId(): string {
        return $this->accountId;
    }

    public function getStatus(): LoadStatusEnum {
        return $this->status;
    }
}
