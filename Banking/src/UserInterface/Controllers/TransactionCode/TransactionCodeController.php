<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionCode;

use App\Banking\Repositories\TransactionCode\TransactionCodeViewRepository;
use App\Banking\UserInterface\Controllers\TransactionCode\Requests\FindTransactionCodesRequest;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

final class TransactionCodeController extends Controller
{
    public function __construct(
        private readonly TransactionCodeViewRepository $transactionCodeRepository,
    ) {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/transaction-code/",
     *   summary="Список категорий транзакций",
     *   tags={"[banking] transaction-code"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="category_id", in="query"),
     *   @OA\Parameter(name="query", in="query"),
     *   @OA\Parameter(name="page", in="query"),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function list(FindTransactionCodesRequest $findRequest): JsonResponse {
        return response()->json($this->transactionCodeRepository->findAllWithRelations(
            $findRequest->string('category_id'),
            $findRequest->string('query')
        ));
    }

}
