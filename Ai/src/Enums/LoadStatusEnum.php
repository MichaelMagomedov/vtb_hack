<?php

declare(strict_types=1);

namespace App\Ai\Enums;

enum LoadStatusEnum: string
{
    case PENDING = 'pending';
    case FAIL = 'fail';
    case SUCCESS = 'success';
    case NEED_STOP = 'need_stop';
    case STOPPED = 'stopped';

    public const IN_PROCESS = [
        self::PENDING,
        self::NEED_STOP
    ];

    public const INTERRUPTED = [
        self::FAIL,
        self::NEED_STOP,
        self::STOPPED
    ];

    // еще идет обработка (need-to-stop тоже тут должен быть так как по факту
    // он сменится только в самом конце обработки следовательно пока он стоит процесс загрузки не завершен)
    public function isInProgress(): bool {
        return in_array($this, self::IN_PROCESS, true);
    }

    // проверка на то что процесс прерван пользователем
    // не убирать от сюда need to stop так как если пользователь остановил парсинг транзакций
    // то те 10+ запущенных процессов парсинга, которые еще в очереди не должны выполнится
    // см SaveParsedTransactionsDataJob.php
    public function isInterrupted(): bool {
        return in_array($this, self::INTERRUPTED, true);
    }
}
