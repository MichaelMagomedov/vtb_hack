<?php

declare(strict_types=1);

namespace App\Ai\Jobs;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Entities\AiThreadEntity;
use App\Ai\Enums\ChatGptRunStatusEnum;
use App\Ai\Exceptions\AiAssistantMaxAttemptException;
use App\Ai\Exceptions\AiAssistantRequestException;
use App\Ai\Repositories\AiAssistant\AiAssistantRepository;
use App\Ai\Repositories\AiThreadEntity\AiThreadEntityRepository;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Dto\UpdateLoadStatusDto;
use App\Ai\Services\Load\LoadService;
use App\Banking\Jobs\ProcessFinalTransactionFileLoadJob;
use App\Root\Exceptions\RuntimeUserFriendlyLoggableException;
use App\Root\Jobs\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use JsonException;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * TODO ничего не менять очень опасно
 */
class ProcessAssistantRunJob implements ShouldQueue
{
    use Queueable, Batchable, Dispatchable, InteractsWithQueue;

    // тут такая петрушка $runAttempt и $failRunAttempt это параметры отвечающие
    // за то сколько раз мы можем проверить или перезапустить проверку chat GPT run процесса
    // а tries это уже laravel механизм, если у нас что-то не получилось в самом коде (например обратиться к api chat gpt)
    // то пытаемся повторить эту же самую операцию
    public $tries = 2;

    // после ошибки ждем 5 секунд после ошибки и пытаемся запустить заново
    public $backoff = 5;

    // так как мы не перезапускаем текущую job а добавляем в цепочку новую, то мы вручную передаем run attempt в новую джобу
    private const MAX_RUN_ATTEMPT = 15;

    // максимальное количество перезапусков job если в chat gpt что то пошло не так
    private const MAX_FAIL_RUN_ATTEMPT = 2;

    public function __construct(
        public string  $threadId,
        public string  $loadId,
        public array   $params,
        public ?string $runId = null,
        public int     $runAttempt = 1,
        public int     $failRunAttempt = 1,
    ) {
    }

    /**
     * эта штука делает так:
     *  1) если runId не передан то пытаемся запустить команду в thread (добавляем новую джобу после текущей))
     *  2) если команда в статусе "in progress" то через секунду еще раз проверяем статус пока не завершиться (добавляем новую джобу после текущей)
     *  3) Если команда завершилась, то записываем результат выполнения это команды в $aiThreadEntityRepository
     */
    public function handle(
        ConnectionInterface      $connection,
        LoadRepository           $loadRepository,
        AiAssistantRepository    $aiAssistantRepository,
        AiThreadEntityRepository $aiThreadEntityRepository
    ): void {
        try {
            $connection->beginTransaction();
            $load = $loadRepository->findById($this->loadId);
            // если загрузка была остановлена или зафейлена то дальше не идем
            if ($load->getStatus()->isInterrupted()) {
                $connection->commit();
                return;
            }

            // инициализируем запуск команды и вставляем сразу после текущий джобы
            if ($this->runId === null) {
                $this->runGptProcess($aiAssistantRepository);
                $connection->commit();
                return;
            }

            // если еще идет run то тогда заново кладем его в очередь что бы подождать и проверить результат заново
            $run = $aiAssistantRepository->getAssistantRunInfo($this->threadId, $this->runId);
            if (in_array($run->status, array_column(ChatGptRunStatusEnum::inProcess(), 'value'))) {
                $this->rerunCheckGptProcessJob($run);
                $connection->commit();
                return;
            }

            // если что то не так со стороны chatgpt то пытаемся перезапустить run до максимального количества попыток
            if (in_array($run->status, array_column(ChatGptRunStatusEnum::fail(), 'value'))) {
                $this->rerunFailedGptProcess($aiAssistantRepository, $run);
                $connection->commit();
                return;
            }

            // когда все ок сохраняем результат chatgpt и вызываем следующую джобу
            if (in_array($run->status, array_column(ChatGptRunStatusEnum::complete(), 'value'))) {
                $this->processSuccessResult($loadRepository, $aiThreadEntityRepository, $run);
                $connection->commit();
                return;
            }
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollBack();
            // логируем исключение, но не повторяем попытку так как это намеренная ошибка
            if ($exception instanceof RuntimeUserFriendlyLoggableException) {
                report($exception);
                return;
            }
            Log::emergency($exception->getMessage());
            // Пробрасываем другие исключения для срабатывания $tries
            throw $exception;
        }
    }

    // обрабатываем если вообще никак не смогли обработать процесс от GPT (если вообще все попытки были исчерпаны)
    public function failed(Throwable $exception) {
        /** @var LoadService $loadService */
        $loadService = app(LoadService::class);
        $loadService->updateStatus(new UpdateLoadStatusDto($this->loadId, LoadStatusEnum::FAIL, $exception));
        dispatch(new ProcessFinalTransactionFileLoadJob($this->loadId));
    }

    // сохраняем json который пришел от нейросети

    /**
     * @throws JsonException
     */
    private function processSuccessResult(
        LoadRepository           $loadRepository,
        AiThreadEntityRepository $aiThreadEntityRepository,
        ThreadRunResponse        $run
    ): void {
        $aiThread = $aiThreadEntityRepository->findByThreadId($this->threadId);
        if ($aiThread === null) {
            $aiThread = $aiThreadEntityRepository->save(new AiThreadEntity(
                Uuid::uuid4()->toString(),
                $this->runId,
                $this->threadId,
                null,
                $this->loadId
            ));
        }
        $toolCals = $run->requiredAction?->submitToolOutputs?->toolCalls ?? [];
        $functionCallParams = count($toolCals) > 0 ? $toolCals[0]->function->arguments : '[]';
        // такой хак что бы проверять валидный ли json пришел от chatgpt
        try {
            $functionCallParams = json_encode(
                json_decode($functionCallParams, null, 512, JSON_THROW_ON_ERROR),
            JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $functionCallParams = '[]';
            report($exception);
        }
        $loadRepository->addOutputCharsCount($this->loadId, strlen(str_replace(' ', '', $functionCallParams)));
        $loadRepository->addOutputCharsCount($this->loadId, str_word_count($functionCallParams));
        $aiThread = $aiThread
            ->withRunId($this->runId)
            ->withFunctionCallParams($functionCallParams);
        $aiThreadEntityRepository->update($aiThread);
    }

    // инициализируем запуск команды и вставляем сразу после текущий джобы
    private function runGptProcess(AiAssistantRepository $aiAssistantRepository): void {
        // на всякий случай останавливаем все run в thread (такого быть не может но на всякий случай что бы ошибка не вылетала)
        $aiAssistantRepository->stopAssistantActiveRun($this->threadId);
        $run = $aiAssistantRepository->createAssistantRun($this->threadId, $this->params);
        // запускаем job для ожидания результата
        $gptCheckProcessJob = new self(
            $this->threadId,
            $this->loadId,
            $this->params,
            $run->id,
            $this->runAttempt
        );
        $this->insertJobAfterCurrent($gptCheckProcessJob);
    }

    /**
     * @throws AiAssistantMaxAttemptException
     */
    // если еще идет run то тогда заново кладем его в очередь что бы подождать и проверить результат заново
    private function rerunCheckGptProcessJob(ThreadRunResponse $run): void {
        if ($this->runAttempt > self::MAX_RUN_ATTEMPT) {
            throw new AiAssistantMaxAttemptException($run->toArray());
        }
        $delay = 4;
        // запускаем заново job для ожидания результата с паузой
        $gptCheckProcessJob = new self(
            $this->threadId,
            $this->loadId,
            $this->params,
            $run->id,
            $this->runAttempt + 1
        );
        $this->insertJobAfterCurrent($gptCheckProcessJob, $delay);
    }

    /**
     * @throws AiAssistantRequestException
     */
    // если что то не так со стороны chatgpt то пытаемся перезапустить run до максимального количества попыток
    private function rerunFailedGptProcess(AiAssistantRepository $aiAssistantRepository, ThreadRunResponse $run): void {
        if ($this->failRunAttempt > self::MAX_FAIL_RUN_ATTEMPT) {
            throw new AiAssistantRequestException($run->toArray());
        }
        $delay = 4;
        $newRun = $aiAssistantRepository->createAssistantRun($this->threadId, $this->params);
        // запускаем заново job для ожидания результата с паузой
        $gptCheckProcessJob = new self(
            $this->threadId,
            $this->loadId,
            $this->params,
            $newRun->id,
            $this->runAttempt + 1,
            $this->failRunAttempt + 1
        );
        $this->insertJobAfterCurrent($gptCheckProcessJob, $delay);
    }
}
