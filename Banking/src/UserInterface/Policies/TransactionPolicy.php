<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Policies;

use App\Auth\Utils\Consumer\Consumer;
use App\Banking\Repositories\Transaction\TransactionRepository;
use Illuminate\Contracts\Auth\Authenticatable;

final class TransactionPolicy
{
    public function __construct(
        private readonly TransactionRepository $repository,
        private readonly ?Consumer             $consumer
    ) {
    }

    public function access(Authenticatable $user, string $id): bool {
        if ($this->repository->belongsUser($id, $user->getAuthIdentifier())) {
            return true;
        }

        // тут можно добавить проверки для дргих ролей
        return false;
    }
}
