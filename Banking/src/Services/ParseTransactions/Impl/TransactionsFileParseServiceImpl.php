<?php

declare(strict_types=1);

namespace App\Banking\Services\ParseTransactions\Impl;

use App\Ai\Enums\LoadStatusEnum;
use App\Ai\Enums\LoadTypeEnum;
use App\Ai\Repositories\AiPrompt\AiPromptRepository;
use App\Ai\Repositories\Load\LoadRepository;
use App\Ai\Services\Load\Dto\CreateLoadDto;
use App\Ai\Services\Load\Dto\StopAnotherLoadDto;
use App\Ai\Services\Load\Dto\UpdateLoadDto;
use App\Ai\Services\Load\LoadService;
use App\Banking\Entities\AccountEntity;
use App\Banking\Enums\AccountTypeEnum;
use App\Banking\Jobs\ProcessFinalTransactionFileLoadJob;
use App\Banking\Jobs\SaveParsedAccountDataJob;
use App\Banking\Jobs\SaveParsedTransactionsDataJob;
use App\Banking\Jobs\SendPendingParsingMessageJob;
use App\Banking\Services\Account\AccountService;
use App\Banking\Services\Account\Dto\GetOrCreateAccountDto;
use App\Banking\Services\AccountBalance\Dto\CreateAccountBalanceDto;
use App\Banking\Services\ParseTransactions\Dto\CreateAccountByParsedDataParamsDto;
use App\Banking\Services\ParseTransactions\Dto\StartParseTransactionsFileDto;
use App\Banking\Services\ParseTransactions\Exceptions\ParseAccountNumberNotFoundException;
use App\Banking\Services\ParseTransactions\Exceptions\ParseAccountSuccessAttemptException;
use App\Banking\Services\ParseTransactions\Exceptions\ParseTransactionFileExtensionException;
use App\Banking\Services\ParseTransactions\TransactionsFileParseService;
use App\PersonalData\Repositories\User\UserRepository;
use App\Storage\Services\TelegramFile\TelegramFileService;
use DateTime;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Bus;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;
use Throwable;

/**
 * В этом сервисе все что связано с парсингом файла через ИИ
 * Все остальные операции нужно распределять по сервисам и вызывать уже сервисы а не пихать все сюды
 */
class TransactionsFileParseServiceImpl implements TransactionsFileParseService
{
    private const TEST_USERNAME = 'michael_magomedov';

    private const MAX_SUCCESS_LOAD_PER_HOUR = 10;

    private const MAX_PAGES_PARSE = 15;

    private const ALLOWED_EXTENSION = 'pdf';

    public function __construct(
        private readonly LoggerInterface         $logger,
        #[Storage('local')] protected Filesystem $filesystem,
        private readonly ConnectionInterface     $connection,
        private readonly AiPromptRepository      $aiPromptRepository,
        private readonly UserRepository          $userRepository,
        private readonly AccountService          $accountService,
        private readonly LoadService             $loadService,
        private readonly LoadRepository          $loadRepository,
        private readonly TelegramFileService     $telegramFileService,
    ) {
    }

    /**
     * Схема такая
     * 1) Получаем текст файла
     * 2) Задаем вопрос ассистенту
     * 3) Крутим jobs пока ждем его полного ответа
     * 4) Выполняем job с полученным ответом
     *
     * Главное запомнить что мы передаем afterJob который выполнится после полного ответа ассистента
     * */
    public function startParse(StartParseTransactionsFileDto $parseParams): void {
        try {
            $this->connection->beginTransaction();

            $fileInfo = $this->telegramFileService->getInfo($parseParams->getPath());
            // пустой file extension присылает телеграм когда проходит больше 1 суток
            if (!empty($fileInfo->getExtension()) && mb_strtolower($fileInfo->getExtension()) !== self::ALLOWED_EXTENSION) {
                throw new ParseTransactionFileExtensionException(['path' => $parseParams->getPath()]);
            }

            // создаем загрузку что бы в ней логировать статус, что бы при не успехе восстановить данные из старой загрузки
            // плюс часть параметров сторим в load например userId или accountId что бы не гонять все эти данные через job и сервисы
            $load = $this->loadService->create(new CreateLoadDto(
                $parseParams->getUserId(),
                $parseParams->getChatId(),
                LoadTypeEnum::PARSE_TRANSACTION
            ));

            // получаем текст файла
            $transactionsFilePath = $this->telegramFileService->download($parseParams->getPath());
            $accountText = (new Pdf())
                ->setPdf($this->filesystem->path($transactionsFilePath))
                ->setOptions(['layout', 'r 96'])
                ->addOptions(['f 1'])
                ->addOptions(['l 1'])
                ->text();

            // запускаем создание счета
            $saveAccountDataJob = new SaveParsedAccountDataJob($load->getId(), $transactionsFilePath);
            $this->aiPromptRepository->prepareAccountDataAndRunAction(
                $accountText,
                $load,
                $saveAccountDataJob
            )->catch(function () use ($load) {
                // делаем только в catch так как после парсинга данных аккаунта
                // мы запускам еще jobs и УЖЕ после них мы сделаем ProcessFinalTransactionFileLoadJob
                dispatch(new ProcessFinalTransactionFileLoadJob($load->getId()));
            })->dispatch();
            dispatch(new SendPendingParsingMessageJob($load->getId(), 0))->delay(now()->addSeconds(5));

            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /** В самих методах ничего не осхраняем а только вызываем сохранение в сущностей в других сервисах */
    public function createAccountByParsedData(CreateAccountByParsedDataParamsDto $params): AccountEntity {
        try {
            $this->connection->beginTransaction();
            $load = $this->loadRepository->findById($params->getLoadId());

            // создаем из полученных от нейросети данных счет
            $parsedData = $params->getParsedData();
            $this->logger->debug('Информация в загрузке: ' . $load->getId(), $parsedData);
            if (empty($parsedData['number'])) {
                throw new ParseAccountNumberNotFoundException();
            }
            $account = $this->accountService->getOrCreate(new GetOrCreateAccountDto(
                $load->getUserId(),
                $parsedData['number'],
                !empty($parsedData['type'])
                    ? AccountTypeEnum::from($parsedData['type'])
                    : AccountTypeEnum::DEBIT,
                !empty($parsedData['bank_id']) && Uuid::isValid($parsedData['bank_id'])
                    ? $parsedData['bank_id']
                    : null,
                !empty($parsedData['name'])
                    ? $parsedData['name']
                    : null,
                !empty($parsedData['currency_id']) && Uuid::isValid($parsedData['currency_id'])
                    ? $parsedData['currency_id']
                    : null,
                !empty($parsedData['bank_reason'])
                    ? $parsedData['bank_reason']
                    : null,
                !empty($parsedData['currency_reason'])
                    ? $parsedData['currency_reason']
                    : null
            ));

            // останавливаем паралельные процессы загрузки по этому аккаунту
            // что бы не получилось путаницы
            $this->loadService->stopAnotherLoad(new StopAnotherLoadDto(
                $load->getUserId(),
                $load->getType(),
                $load,
                $account
            ));
            // проверяем допустимое количество загрузок
            $user = $this->userRepository->findById($load->getUserId());
            $successLoadCountPerHour = $this->loadRepository->findCountSuccessLoadByPrevHour($account->getId());
            if ($successLoadCountPerHour > self::MAX_SUCCESS_LOAD_PER_HOUR && $user->getUsername() !== self::TEST_USERNAME) {
                throw new ParseAccountSuccessAttemptException(self::MAX_SUCCESS_LOAD_PER_HOUR);
            }

            $this->loadService->update(new UpdateLoadDto($load->getId(), LoadStatusEnum::PENDING, $account->getId()));

            // сохраняем корректировки баланса
            $accountBalancesData = [];
            if (!empty($parsedData['start_date']) && !empty($parsedData['start_balance'])) {
                $accountBalancesData[] = new CreateAccountBalanceDto(
                    floatval(floatval(str_replace(",", "", $parsedData['start_balance']))),
                    new DateTime($parsedData['start_date']),
                    $account->getId(),
                    $load->getId()
                );
            }
            if (!empty($parsedData['end_date']) && !empty($parsedData['end_balance'])) {
                $accountBalancesData[] = new CreateAccountBalanceDto(
                    floatval(floatval(str_replace(",", "", $parsedData['end_balance']))),
                    new DateTime($parsedData['end_date']),
                    $account->getId(),
                    $load->getId()
                );
            }
            if (!empty($accountBalancesData)) {
                // временно выключаем сохранение баланса пользователя после парса транзакций
                // $this->accountBalanceService->createAfterParse($accountBalancesData);
            }

            // создаем цепочки заданий на сохранение банковских операций
            $parser = new Parser();
            $pdf = $parser->parseContent($this->filesystem->get($params->getPdfPath()));
            $transactionsParseBatch = [];

            // обычно операции идут самые дальние на первой странице а ближайшие на последней
            $pages = array_slice(array_reverse($pdf->getPages()), 0, self::MAX_PAGES_PARSE);
            foreach ($pages as $index => $page) {

                // что бы сохранить отступы мы используем другую библиотеку для парсинга pdf
                $pageNumber = $index + 1;
                $transactionsText = (new Pdf())
                    ->setPdf($this->filesystem->path($params->getPdfPath()))
                    ->setOptions(['layout', 'r 96'])
                    ->addOptions(["f $pageNumber"])
                    ->addOptions(["l $pageNumber"])
                    ->text();

                // нам нужно сделать так, что если что то пошло не так мы удаляем все распознанные операции и восстанавливаем старые
                // по этому сначала собираем все цепочки
                $saveTransactionDataJob = new SaveParsedTransactionsDataJob($params->getLoadId());
                $pendingChain = $this->aiPromptRepository->prepareTransactionsDataAndRunAction(
                    $transactionsText,
                    $load,
                    $saveTransactionDataJob
                );
                // переделываем pending chain на массив job что бы отправить их потом в batch (небольшой костылек)
                $transactionsParseBatch[] = [$pendingChain->job, ...$pendingChain->chain];
            }

            // в batch и рулим транзакциями в конце (добавляем к ним обработки и кетчи)
            // тут получается такая конструкция [prompt,run,prompt],[prompt,run,prompt] итд
            // каждая цепочка выполняется последовательно но все цепочки выполняются паралельно
            Bus::batch($transactionsParseBatch)->finally(function () use ($params) {
                dispatch(new ProcessFinalTransactionFileLoadJob($params->getLoadId()));
            })->dispatch();

            $this->connection->commit();
            return $account;
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        } finally {
            $this->filesystem->delete($params->getPdfPath());
        }
    }
}
