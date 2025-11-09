<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

final class BankEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string  $id,
        private readonly string  $name,
        private readonly string  $alias,
        private readonly ?string $logo = null,
        private readonly ?string $color = null,
        private readonly ?string $lkUrl = null,
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getLogo(): string {
        return $this->logo;
    }

    public function getAlias(): string {
        return $this->alias;
    }

    public function getLkUrl(): ?string
    {
        return $this->lkUrl;
    }
}
