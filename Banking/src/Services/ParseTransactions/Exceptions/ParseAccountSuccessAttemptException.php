<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Exceptions;

use App\Root\Exceptions\RuntimeUserFriendlyLoggableException;

final class ParseAccountSuccessAttemptException extends RuntimeUserFriendlyLoggableException
{
    private int $maxSuccessLoadPerHour;

    public function __construct(int $maxSuccessLoadPerHour) {
        $this->maxSuccessLoadPerHour = $maxSuccessLoadPerHour;
        parent::__construct(['maxSuccessLoadPerHour' => $maxSuccessLoadPerHour]);
    }

    /**
     * @inheritDoc
     */
    protected function getTranslateMessage(): string {
        return trans('banking::transaction.job_exceptions.success_parse_count_exception', [
            'count' => $this->maxSuccessLoadPerHour,
            'minutes' => 59 - date('i')
        ]);
    }

    protected function getLoggingReason(): string {
        return 'Кто-то из пользователей достиг максимального количества загрузок файла банкига за час,
                но этот случай не обработан со стороны бота, по этому в ответ он получит молчание.
                Если будут такие ошибки то нужно научится обрабатывать их';
    }
}
