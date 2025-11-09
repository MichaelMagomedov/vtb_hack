<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionParse;

use App\Banking\Services\ParseTransactions\Dto\StartParseTransactionsFileDto;
use App\Banking\Services\ParseTransactions\Dto\StartParseTransactionsHtmlDto;
use App\Banking\Services\ParseTransactions\TransactionsFileParseService;
use App\Banking\Services\ParseTransactions\TransactionsHtmlParseService;
use App\Banking\UserInterface\Controllers\TransactionParse\Requests\ParseTransactionsHtmlRequest;
use App\Banking\UserInterface\Controllers\TransactionParse\Requests\ParseTransactionsRequest;
use App\PersonalData\Enums\UsernameTypeEnum;
use App\PersonalData\Repositories\Messenger\MessengerRepository;
use App\PersonalData\Services\User\UserService;
use App\Root\Exceptions\UserFriendlyException;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Throwable;

final class TransactionParseController extends Controller
{
    public function __construct(
        private readonly UserService                  $userService,
        private readonly MessengerRepository          $messengerRepository,
        private readonly TransactionsFileParseService $transactionsFileParseService,
        private readonly TransactionsHtmlParseService $transactionsHtmlParseService,
    )
    {
    }

    /**
     * ЭТО экшн на парсинг из банкивского файла
     *
     * @OA\Get(
     *   path="/api/v1/banking/transaction-parse/",
     *   summary="Запустить парсинг файла, делаю гет метод что бы удобнее его дергать",
     *   tags={"[banking] transaction-parse"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="file_id", in="query", required=true),
     *   @OA\Parameter(name="chat_id", in="query", required=true),
     *   @OA\Parameter(name="username", in="query", required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function start(ParseTransactionsRequest $parseTransactionsRequest): JsonResponse
    {
        try {
            $user = $this->userService->createByUsername(
                $parseTransactionsRequest->string('username'),
                UsernameTypeEnum::TELEGRAM
            );
            $this->messengerRepository->createChat(
                $parseTransactionsRequest->string('chat_id'),
                $parseTransactionsRequest->string('username'),
            );
            $this->transactionsFileParseService->startParse(new StartParseTransactionsFileDto(
                $user->getId(),
                $parseTransactionsRequest->string('chat_id'),
                $parseTransactionsRequest->string('file_id')
            ));
        } catch (Throwable $exception) {
            // так как puzzle bot не умеет обрабатывать отправку сообщений при ошибке, обрабатываем их тут
            $message = $exception instanceof UserFriendlyException
                ? $exception->getMessage()
                : trans('banking::transaction.job_exceptions.save_account_data_unknown_exception');
            $this->messengerRepository->sendMessage($parseTransactionsRequest->string('chat_id'), $message);
            throw $exception;
        }
        return response()->json(['success' => true]);
    }


    /**
     * ЭТО экшн на парсинг HTML с транзакциями
     *
     * @OA\Post(
     *   path="/api/v1/banking/transaction-parse/parse-html",
     *   summary="Запустить парсинг html с транзакциями из лк",
     *   tags={"[banking] transaction-parse"},
     *   security={{"bearer": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"account_id", "html"},
     *         @OA\Property(property="account_id", type="string"),
     *         @OA\Property(property="html", type="string"),
     *         @OA\Property(property="load_id"),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function parseHtml(ParseTransactionsHtmlRequest $parseHtmlRequest): JsonResponse
    {
        $this->authorize('account', $parseHtmlRequest->string('account_id'));
        $this->transactionsHtmlParseService->startParse(new StartParseTransactionsHtmlDto(
            $parseHtmlRequest->string('account_id'),
            $parseHtmlRequest->string('html')
        ));

        return response()->json(['success' => true]);
    }
}
