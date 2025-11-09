<?php

declare(strict_types=1);

namespace App\Ai\Services\Load;

use App\Ai\Entities\LoadEntity;
use App\Ai\Services\Load\Dto\CreateLoadDto;
use App\Ai\Services\Load\Dto\StopAnotherLoadDto;
use App\Ai\Services\Load\Dto\UpdateLoadDto;
use App\Ai\Services\Load\Dto\UpdateLoadStatusDto;

interface LoadService
{
    public function create(CreateLoadDto $createParams): LoadEntity;

    public function update(UpdateLoadDto $updateParams): LoadEntity;

    // использовать этот метод для смены статуса что бы при обновлении не затирались атрибуты
    // обновляемые в другом процессе
    public function updateStatus(UpdateLoadStatusDto $updateParams): LoadEntity;

    // ставим в need stop
    public function stopAnotherLoad(StopAnotherLoadDto $stopData): void;

    // косвенно этот функционал завсит от команды ClearHungLoadsCommand
    // которая подтираем зависшие процессы или которые сами по себе не остановились при корректном заверешении
    public function isLastInProcessLoadInStack(string $loadId): bool;

    // а тут если процесс не прерван то он success
    // а если требует останови то ставим следующий шан => остановлен
    public function finalize(LoadEntity $loadEntity): LoadEntity;
}
