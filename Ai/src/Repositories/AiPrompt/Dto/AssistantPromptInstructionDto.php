<?php

declare(strict_types=1);

namespace App\Ai\Repositories\Ai\Dto;

final class AssistantPromptInstructionDto
{
    public function __construct(
        private readonly string $prompt,
        private readonly bool   $useFileSearch = false
    ) {
    }

    public function getPrompt(): string {
        return $this->prompt;
    }

    public function getUseFileSearch(): bool {
        return $this->useFileSearch;
    }
}
