<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionCategory;

use App\Banking\Repositories\TransactionCategory\TransactionCategoryViewRepository;
use App\Banking\UserInterface\Controllers\TransactionCategory\Requests\FindTransactionCategoriesByUserRequest;
use App\Banking\UserInterface\Controllers\TransactionCategory\Requests\FindTransactionCategoriesRequest;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

final class TransactionCategoryController extends Controller
{
    public function __construct(
        private readonly ?Authenticatable                  $user = null,
        private readonly TransactionCategoryViewRepository $transactionCategoryRepository
    ) {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/transaction-category/",
     *   summary="Список категорий транзакций",
     *   tags={"[banking] transaction-category"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="code_id", in="query"),
     *   @OA\Parameter(name="query", in="query"),
     *   @OA\Parameter(name="page", in="query"),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function list(FindTransactionCategoriesRequest $findRequest): JsonResponse {
        return response()->json($this->transactionCategoryRepository->findAllWithRelations(
            $this->user->getAuthIdentifier(),
            $findRequest->string('code_id'),
            $findRequest->string('query')
        ));
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/transaction-category/user",
     *   summary="Список категорий трат пользователя за вреся",
     *   tags={"[banking] transaction-category"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="from", in="query"),
     *   @OA\Parameter(name="to", in="query"),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function byUser(FindTransactionCategoriesByUserRequest $findByUserRequest): JsonResponse {
        return response()->json($this->transactionCategoryRepository->findAllByUser(
            $this->user->getAuthIdentifier(),
            $findByUserRequest->dateTime('from'),
            $findByUserRequest->dateTime('to')
        ));
    }
}
