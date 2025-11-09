<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

final class CurrencyEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string $id,
        private readonly  string $code,
        private readonly  string $alias,
        private readonly  string $icon,
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getCode(): string {
        return $this->code;
    }

    public function getAlias(): string {
        return $this->alias;
    }

    public function getIcon(): string {
        return $this->icon;
    }
}
