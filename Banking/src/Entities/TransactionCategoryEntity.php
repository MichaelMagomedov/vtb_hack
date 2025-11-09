<?php

declare(strict_types=1);

namespace App\Banking\Entities;

use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

final class TransactionCategoryEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string  $id,
        private readonly string  $name,
        private readonly string  $color,
        private readonly ?string $logo = null,
        private readonly ?int $order = null,
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getLogo(): ?string {
        return $this->logo;
    }

    public function getName(): string {
        return mb_strtolower($this->name);
    }

    public function getColor(): string {
        return $this->color;
    }

    public function getOrder(): ?int {
        return $this->order;
    }
}
