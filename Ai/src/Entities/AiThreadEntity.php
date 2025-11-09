<?php

declare(strict_types=1);

namespace App\Ai\Entities;

use App\Root\Utils\HistoryFields\Traits\HasHistoryFields;

final class AiThreadEntity
{
    use HasHistoryFields;

    public function __construct(
        private readonly string $id,
        private string          $runId,
        private readonly string $thread,
        private ?string         $functionCallParams = null,
        private ?string         $loadId = null
    ) {
    }

    public function getId(): string {
        return $this->id;
    }

    public function getRunId(): string {
        return $this->runId;
    }

    public function getThread(): string {
        return $this->thread;
    }

    public function getFunctionCallParams(): ?string {
        return $this->functionCallParams;
    }

    public function getLoadId(): ?string {
        return $this->loadId;
    }

    public function withFunctionCallParams(?string $functionCallParams): self {
        $new = clone $this;
        $new->functionCallParams = $functionCallParams;
        return $new;
    }

    public function withRunId(?string $runId): self {
        $new = clone $this;
        $new->runId = $runId;
        return $new;
    }
}
