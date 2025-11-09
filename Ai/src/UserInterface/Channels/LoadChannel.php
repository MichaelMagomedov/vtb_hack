<?php

namespace App\Ai\UserInterface\Channels;

use Illuminate\Foundation\Auth\User as Authenticatable;

class LoadChannel
{
    /**
     * Тут делать проверку что юзер может прослушивать этот канал
     *
     * @example Gate::authorize('user-recommendation_settings', $userRecommendationsSettingsId);
     */
    public function join(Authenticatable $user, string $userId) {
        // так как все loadId прикреплены за пользователем
        // это не стандартный вид канала так что тут просто проверяем что айди пользователя в названии канала
        // совпадает с текущем авторизованным
        // иначе проверяли бы используя gate
        return $user->getAuthIdentifier() === $userId;
    }
}
