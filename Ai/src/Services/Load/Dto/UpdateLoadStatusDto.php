<?php

declare(strict_types=1);

namespace App\Ai\Services\Load\Dto;

use App\Ai\Enums\LoadStatusEnum;
use Throwable;

final class UpdateLoadStatusDto
{
    public function __construct(
        private readonly string         $id,
        private readonly LoadStatusEnum $status,
        private readonly ?Throwable $exception = null
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getStatus(): LoadStatusEnum {
        return $this->status;
    }

    public function getException(): ?Throwable {
        return $this->exception;
    }
}
