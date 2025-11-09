<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction;

use App\Banking\Enums\TransactionTypeEnum;
use App\Banking\Repositories\Transaction\TransactionRepository;
use App\Banking\Repositories\Transaction\TransactionViewRepository;
use App\Banking\Services\Transaction\Dto\CreateTransactionDto;
use App\Banking\Services\Transaction\Dto\UpdateTransactionDto;
use App\Banking\Services\Transaction\Dto\UpdateTransactionPatternDto;
use App\Banking\Services\Transaction\Exceptions\SaveFutureTransactionException;
use App\Banking\Services\Transaction\TransactionService;
use App\Banking\UserInterface\Controllers\Transaction\Requests\CreateTransactionsRequest;
use App\Banking\UserInterface\Controllers\Transaction\Requests\DeleteTransactionRequest;
use App\Banking\UserInterface\Controllers\Transaction\Requests\FindTransactionsRequest;
use App\Banking\UserInterface\Controllers\Transaction\Requests\GetDestinationAutocompleteRequest;
use App\Banking\UserInterface\Controllers\Transaction\Requests\GetTransactionRequest;
use App\Banking\UserInterface\Controllers\Transaction\Requests\UpdateTransactionsOrderRequest;
use App\Banking\UserInterface\Controllers\Transaction\Requests\UpdateTransactionsRequest;
use App\Banking\UserInterface\Controllers\Transaction\Requests\VerifyTransactionsRequest;
use App\Root\UserInterface\Controllers\Controller;
use DateTime;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

final class TransactionController extends Controller
{
    public function __construct(
        private readonly ?Authenticatable          $user = null,
        private readonly TransactionService        $transactionService,
        private readonly TransactionViewRepository $transactionViewRepository,
    )
    {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/transaction/",
     *   summary="Список транзакций пользова",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="start_time", in="query"),
     *   @OA\Parameter(name="end_time", in="query"),
     *   @OA\Parameter(name="query", in="query"),
     *   @OA\Parameter(name="page", in="query", required=true),
     *   @OA\Parameter(name="category_id", in="query"),
     *   @OA\Parameter(name="allow_empty_category", in="query"),
     *   @OA\Parameter(name="only_not_verified", in="query"),
     *   @OA\Parameter(
     *       name="exclude_types[]",
     *       in="query",
     *       @OA\Schema(
     *           type="array",
     *           @OA\Items(
     *               type="enum",
     *               enum={"simple","sbp","between_accounts","hold"},
     *           ),
     *       ),
     *   ),
     *   @OA\Parameter(name="exclude_income", in="query"),
     *   @OA\Parameter(name="exclude_expense", in="query"),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public
    function list(FindTransactionsRequest $transactionsRequest): JsonResponse
    {
        $transactions = $this->transactionViewRepository->findByUserIdWithRelations(
            $this->user->getAuthIdentifier(),
            $transactionsRequest->string('query'),
            $transactionsRequest->dateTime('start_time'),
            $transactionsRequest->dateTime('end_time'),
            $transactionsRequest->input('exclude_types'),
            $transactionsRequest->boolean('exclude_income'),
            $transactionsRequest->string('category_id'),
            $transactionsRequest->boolean('allow_empty_category'),
            $transactionsRequest->boolean('only_not_verified'),
            $transactionsRequest->boolean('exclude_expense'),
        );
        return response()->json($transactions);
    }


    /**
     * @OA\Get(
     *   path="/api/v1/banking/transaction/{id}",
     *   summary="Получить информацию о конкретной транзакции",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function get(GetTransactionRequest $getTransactionRequest): JsonResponse
    {
        $this->authorize('transaction', $getTransactionRequest->route('id'));
        $transaction = $this->transactionViewRepository->findByIdWithRelations(
            $getTransactionRequest->route('id')
        );
        return response()->json($transaction);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/banking/transaction/{id}",
     *   summary="Удалить транзакцию",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function delete(DeleteTransactionRequest $deleteTransactionRequest): JsonResponse
    {
        $this->authorize('transaction', $deleteTransactionRequest->route('id'));
        $this->transactionService->delete($deleteTransactionRequest->route('id'));
        return response()->json(['success' => true]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/banking/transaction/order",
     *   summary="Обновить порядок транзакций",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(
     *           property="ids",
     *           type="array",
     *           @OA\Items(type="string"),
    )
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function updateOrder(UpdateTransactionsOrderRequest $updateOrderRequest): JsonResponse
    {
        foreach ($updateOrderRequest->input('ids') as $id) {
            $this->authorize('transaction', $id);
        }
        $this->transactionService->updateOrder($updateOrderRequest->input('ids'));
        return response()->json(['success' => true]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/banking/transaction/{id}",
     *   summary="Обновить транзакцию",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"amount", "date", "type", "short_desc"},
     *         @OA\Property(property="amount", type="float"),
     *         @OA\Property(property="date", type="string"),
     *         @OA\Property(property="type", type="string", enum={"simple", "sbp", "between_accounts"}),
     *         @OA\Property(property="desc", type="string"),
     *         @OA\Property(property="short_desc", type="string"),
     *         @OA\Property(property="destination", type="string"),
     *         @OA\Property(property="category_id", type="string"),
     *         @OA\Property(property="code_id", type="string"),
     *         @OA\Property(property="color", type="string"),
     *         @OA\Property(
     *             property="transaction_pattern",
     *             type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="category_id", type="string"),
     *                 @OA\Property(property="code_id", type="string"),
     *         ),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function update(UpdateTransactionsRequest $updateRequests): JsonResponse
    {
        $this->authorize('transaction', $updateRequests->route('id'));
        $this->authorize('account', $updateRequests->string('account_id'));
        if ($updateRequests->dateTime('date') > (new DateTime())->setTime(23, 59, 59)) {
            throw new SaveFutureTransactionException();
        }
        $transaction = $this->transactionService->update(new UpdateTransactionDto(
            $updateRequests->route('id'),
            $updateRequests->string('account_id'),
            $updateRequests->float('amount'),
            $updateRequests->dateTime('date'),
            TransactionTypeEnum::from($updateRequests->string('type')),
            $updateRequests->string('short_desc'),
            $updateRequests->string('desc'),
            $updateRequests->string('destination'),
            $updateRequests->string('category_id'),
            $updateRequests->string('code_id'),
            $updateRequests->string('color'),
            $updateRequests->input('transaction_pattern')
                ? new UpdateTransactionPatternDto(
                $updateRequests->string('transaction_pattern.id'),
                $updateRequests->string('transaction_pattern.category_id'),
                $updateRequests->string('transaction_pattern.code_id')
            ) : null,

        ));
        return response()->json(
            $this->transactionViewRepository->findByIdWithRelations($transaction->getId())
        );
    }

    /**
     * @OA\Post(
     *   path="/api/v1/banking/transaction/",
     *   summary="Создать счет",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"account_id", "amount", "date", "type", "short_desc"},
     *         @OA\Property(property="account_id", type="string"),
     *         @OA\Property(property="amount", type="float"),
     *         @OA\Property(property="date", type="string"),
     *         @OA\Property(property="type", type="string", enum={"simple", "sbp", "between_accounts"}),
     *         @OA\Property(property="desc", type="string"),
     *         @OA\Property(property="short_desc", type="string"),
     *         @OA\Property(property="destination", type="string"),
     *         @OA\Property(property="category_id", type="string"),
     *         @OA\Property(property="code_id", type="string"),
     *         @OA\Property(property="color", type="string"),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function create(CreateTransactionsRequest $createRequests): JsonResponse
    {
        $this->authorize('account', $createRequests->string('account_id'));
        /**
         * нельзя создавать будующие операции, только в пределах одного дня
         * @see TransactionRepository::findDateUntilWhichParseNotAvailable - смотреть где используется
         */
        if ($createRequests->dateTime('date') > (new DateTime())->setTime(23, 59, 59)) {
            throw new SaveFutureTransactionException();
        }
        $transaction = $this->transactionService->save(new CreateTransactionDto(
            $createRequests->string('account_id'),
            $createRequests->float('amount'),
            $createRequests->string('short_desc'),
            null,
            $createRequests->string('desc'),
            $createRequests->dateTime('date'),
            TransactionTypeEnum::from($createRequests->string('type')),
            null,
            $createRequests->string('destination'),
            null,
            $createRequests->string('category_id'),
            null,
            $createRequests->string('code_id'),
            $createRequests->string('color'),
        ),
            false,
            true,
            false
        );
        return response()->json(
            $this->transactionViewRepository->findByIdWithRelations($transaction->getId())
        );
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/transaction/destination",
     *   summary="Получить автокомплит для получаетял",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="query", in="query" ,required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function destinationAutocomplete(GetDestinationAutocompleteRequest $getRequest): JsonResponse
    {
        return response()->json(
            $this->transactionViewRepository->findDestinationAutocomplete(
                $getRequest->string('query'),
                $this->user->getAuthIdentifier(),
            )
        );
    }

    /**
     * @OA\Post(
     *   path="/api/v1/banking/transaction/verify",
     *   summary="Проверить транзакции",
     *   tags={"[banking] transaction"},
     *   security={{"bearer": {}}},
     *   @OA\RequestBody(
     *      required=true,
     *      @OA\MediaType(
     *        mediaType="application/json",
     *        @OA\Schema(
     *          @OA\Property(property="id", type="string"),
     *          @OA\Property(property="category_id", type="string"),
     *          @OA\Property(property="allow_empty_category", type="boolean"),
     *          @OA\Property(property="start_time", type="string"),
     *          @OA\Property(property="end_time", type="string"),
     *          @OA\Property(property="exclude_income", type="boolean"),
     *          @OA\Property(property="exclude_expense", type="boolean"),
     *        ),
     *      ),
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function verify(VerifyTransactionsRequest $verifyTransactionsRequest): JsonResponse
    {
        if ($verifyTransactionsRequest->string('id') !== null) {
            $this->authorize('transaction', $verifyTransactionsRequest->string('id'));
        }
        $this->transactionService->verify(
            $this->user->getAuthIdentifier(),
            $verifyTransactionsRequest->string('id'),
            $verifyTransactionsRequest->string('category_id'),
            $verifyTransactionsRequest->boolean('allow_empty_category'),
            $verifyTransactionsRequest->dateTime('start_time'),
            $verifyTransactionsRequest->dateTime('end_time'),
            $verifyTransactionsRequest->boolean('exclude_income'),
            $verifyTransactionsRequest->boolean('exclude_expense'),
        );

        return response()->json(['success' => true]);
    }
}
