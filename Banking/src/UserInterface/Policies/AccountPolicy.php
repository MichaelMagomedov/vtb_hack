<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Policies;

use App\Auth\Utils\Consumer\Consumer;
use App\Banking\Repositories\Account\AccountRepository;
use Illuminate\Contracts\Auth\Authenticatable;

final class AccountPolicy
{
    public function __construct(
        private readonly AccountRepository $repository,
        private readonly ?Consumer         $consumer
    ) {
    }

    public function access(Authenticatable $user, string $id): bool {
        $entity = $this->repository->findByid($id);

        if ($entity->getUserId() === $user->getAuthIdentifier()) {
            return true;
        }

        // тут можно добавить проверки для дргих ролей
        return false;
    }
}
