<?php

declare(strict_types=1);

namespace App\Banking\Jobs;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Jobs\AfterAssistantRunJob\AfterAssistantRunJob;
use App\Ai\Repositories\Load\LoadRepository;
use App\PersonalData\Services\Messenger\MessengerService;

class SendPendingParsingMessageJob extends AfterAssistantRunJob
{
    public function __construct(
        public string $loadId,
        public int    $template,
        public int    $attempt = 1,
    ) {
    }

    public function handle(
        LoadRepository   $loadRepository,
        MessengerService $messengerService
    ): void {
        if ($this->attempt > 40) {
            return;
        }
        $load = $loadRepository->findById($this->loadId);
        if ($load->getStatus() === LoadStatusEnum::PENDING) {
            $message = '';
            if ($this->template === 0) {
                $message = trans('banking::transaction.parse_pending_messages.one');
            }
            if ($this->template === 1) {
                $message = trans('banking::transaction.parse_pending_messages.two');
            }
            if ($this->template === 2) {
                $message = trans('banking::transaction.parse_pending_messages.three');
            }
            $messengerService->sendByLoadId($load->getId(), $message);
            $nextPendingJob = new SendPendingParsingMessageJob(
                $this->loadId,
                // если показали все текстовые сообщения, то запускаем их заново
                $this->template > 2 ? 0 : $this->template + 1,
                $this->attempt + 1
            );
            dispatch($nextPendingJob)->delay(now()->addSeconds(7));
        }
    }
}
