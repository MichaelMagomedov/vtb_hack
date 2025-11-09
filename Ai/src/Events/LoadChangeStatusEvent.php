<?php

declare(strict_types=1);

namespace App\Ai\Events;

use App\Ai\Entities\LoadEntity;
use Illuminate\Foundation\Events\Dispatchable;

final class LoadChangeStatusEvent
{
    use Dispatchable;

    public function __construct(
        private readonly LoadEntity $load
    ) {
    }

    public function getLoad(): LoadEntity {
        return $this->load;
    }
}
