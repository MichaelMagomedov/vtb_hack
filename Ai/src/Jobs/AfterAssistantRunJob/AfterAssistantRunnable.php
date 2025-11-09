<?php

declare(strict_types=1);

namespace App\Ai\Jobs\AfterAssistantRunJob;

use Illuminate\Contracts\Queue\ShouldQueue;

interface AfterAssistantRunnable extends ShouldQueue
{
    public function setThreadId(string $threadId);
}
