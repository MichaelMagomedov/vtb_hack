<?php

declare(strict_types=1);

namespace App\Ai\Entities;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Enums\LoadTypeEnum;
use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

/**
 * Вообще это объект синхронизаций для джобов изначально он создавался исключительно
 * для загрузки файла банкинга и параллельного его парсинга, а сейчас используется
 * для  синхронизации любой цепочки job. Вообще правильно его называть больше SyncAiJobObject
 */
final class LoadEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string       $id,
        private readonly string       $userId,
        // toDo вынести потом в LoadTransactionsAdditionDataEntity
        private readonly ?string      $chatId = null,
        private LoadStatusEnum        $status,
        // toDo вынести потом в LoadTransactionsAdditionDataEntity,
        // что бы использовать LoadEntity не только для загрузки транзакций а для всех job
        // связанных с AI (что бы load был полноценным объектом синхронизации без мусорных данных(
        private ?string               $accountId = null,
        private ?string               $reason = null,
        // toDo вынести потом в LoadTransactionsAdditionDataEntity
        private ?string               $lastMessageId = null,
        private ?string               $sysReason = null,
        // count свойства обновлять только через репу что бы при параллельных обработках  не затирать друг друга
        private readonly int          $inputCharsCount = 0,
        private readonly int          $inputWordsCount = 0,
        private readonly int          $outputCharsCount = 0,
        private readonly int          $outputWordsCount = 0,
        private readonly LoadTypeEnum $type = LoadTypeEnum::PARSE_TRANSACTION,
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getUserId(): string {
        return $this->userId;
    }

    public function getChatId(): ?string {
        return $this->chatId;
    }

    public function getStatus(): LoadStatusEnum {
        return $this->status;
    }

    public function getAccountId(): ?string {
        return $this->accountId;
    }


    public function getReason(): ?string {
        return $this->reason;
    }

    public function getLastMessageId(): ?string {
        return $this->lastMessageId;
    }

    public function getSysReason(): ?string {
        return $this->sysReason;
    }

    public function getInputCharsCount(): int {
        return $this->inputCharsCount;
    }

    public function getInputWordsCount(): int {
        return $this->inputWordsCount;
    }

    public function getOutputCharsCount(): int {
        return $this->outputCharsCount;
    }

    public function getOutputWordsCount(): int {
        return $this->outputWordsCount;
    }

    public function getType(): LoadTypeEnum {
        return $this->type;
    }

    public function withAccountId(?string $accountId): self {
        $new = clone $this;
        $new->accountId = $accountId;
        return $new;
    }

    public function withStatus(LoadStatusEnum $status): self {
        $new = clone $this;
        $new->status = $status;
        return $new;
    }

    public function withReason(?string $reason): self {
        $new = clone $this;
        $new->reason = $reason;
        return $new;
    }

    public function withSysReason(?string $sysReason): self {
        $new = clone $this;
        $new->sysReason = $sysReason;
        return $new;
    }

    public function withLastMessageId(?string $lastMessageId): self {
        $new = clone $this;
        $new->lastMessageId = $lastMessageId;
        return $new;
    }
}
