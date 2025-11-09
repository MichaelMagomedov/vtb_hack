<?php

declare(strict_types=1);

namespace App\Ai\Services\Load\Impl;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Entities\LoadEntity;
use App\Ai\Events\LoadChangeStatusEvent;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Dto\CreateLoadDto;
use App\Ai\Services\Load\Dto\StopAnotherLoadDto;
use App\Ai\Services\Load\Dto\UpdateLoadDto;
use App\Ai\Services\Load\Dto\UpdateLoadStatusDto;
use App\Ai\Services\Load\LoadService;
use App\Root\Exceptions\RuntimeUserFriendlyLoggableException;
use App\Root\Exceptions\UserFriendlyException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;

final class LoadServiceImpl implements LoadService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LoadRepository  $repository
    ) {
    }

    public function create(CreateLoadDto $createParams): LoadEntity {
        $load = $this->repository->save(new LoadEntity(
            Uuid::uuid4()->toString(),
            $createParams->getUserId(),
            $createParams->getChatId(),
            LoadStatusEnum::PENDING,
            null,
            null,
            null,
            null,
            0,
            0,
            0,
            0,
            $createParams->getLoadTypeEnum()
        ));

        event(new LoadChangeStatusEvent($load));
        return $load;
    }

    public function update(UpdateLoadDto $updateParams): LoadEntity {
        $load = $this->repository->findByid($updateParams->getId());
        $load = $load
            ->withAccountId($updateParams->getAccountId())
            ->withStatus($updateParams->getStatus());

        $load = $this->repository->update($load);
        event(new LoadChangeStatusEvent($load));
        return $load;
    }

    /**
     * @inheritDoc
     */
    public function stopAnotherLoad(StopAnotherLoadDto $stopData): void {
        $loads = $this->repository->findUserLoadStack(
            $stopData->getUserId(),
            $stopData->getType(),
            $stopData->getAccount() !== null
                ? $stopData->getAccount()->getId()
                : null
        );
        /** @var LoadEntity $load */
        foreach ($loads as $load) {
            if ($load->getId() !== $stopData->getExcludeLoad()->getId()) {
                $this->updateStatus(new UpdateLoadStatusDto($load->getId(), LoadStatusEnum::NEED_STOP));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function isLastInProcessLoadInStack(string $loadId): bool {
        $load = $this->repository->findById($loadId);
        $loadStack = $this->repository->findUserLoadStack($load->getUserId(), $load->getType(), $load->getAccountId());
        // если в стеке вообще нет никаких или текущая транзакция и есть последняя
        if (count($loadStack) === 0 || $loadStack[0]->getId() === $load->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function finalize(LoadEntity $load): LoadEntity {
        $miniStateMachine = [
            LoadStatusEnum::PENDING->value => LoadStatusEnum::SUCCESS,
            LoadStatusEnum::NEED_STOP->value => LoadStatusEnum::STOPPED,
            LoadStatusEnum::FAIL->value => LoadStatusEnum::FAIL
        ];
        $newStatus = $miniStateMachine[$load->getStatus()->value];
        $this->updateStatus(new UpdateLoadStatusDto($load->getId(), $newStatus));
        return $load->withStatus($newStatus);
    }

    /**
     * @inheritDoc
     */
    public function updateStatus(UpdateLoadStatusDto $updateParams): LoadEntity {
        $reason = null;
        $sysReason = null;
        $load = $this->repository->findByid($updateParams->getId());

        $exception = $updateParams->getException();
        if ($exception !== null) {
            $sysReason = mb_substr($exception->getMessage(), 0, 240);
            $reason = $exception instanceof UserFriendlyException
                ? mb_substr($exception->getMessage(), 0, 240)
                : trans('ai::ai.chatgpt_unknown_exception');

            // logging
            $context = ['load_id' => $load->getId()];
            if ($exception instanceof RuntimeUserFriendlyLoggableException) {
                $context = array_merge($context, $exception->getContext());
            }
            $this->logger->log(
                $exception instanceof UserFriendlyException ? LogLevel::WARNING : LogLevel::ERROR,
                $sysReason,
                $context
            );
        }

        $load = $load
            // что бы не перетирать первостенпенную ошибку
            ->withReason($load->getReason() ?? $reason)
            ->withSysReason($load->getSysReason() ?? $sysReason)
            ->withStatus($updateParams->getStatus());
        $load = $this->repository->updateSpecificAttributes($load, ['status', 'reason', 'sys_reason']);
        event(new LoadChangeStatusEvent($load));

        return $load;
    }
}
