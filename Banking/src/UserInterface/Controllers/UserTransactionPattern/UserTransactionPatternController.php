<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\UserTransactionPattern;

use App\Banking\Repositories\UserTransactionPattern\UserTransactionPatternViewRepository;
use App\Banking\UserInterface\Controllers\UserTransactionPattern\Requests\FindUserTransactionPatternByDestinationRequest;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

final class UserTransactionPatternController extends Controller
{
    public function __construct(
        private readonly ?Authenticatable                     $user = null,
        private readonly UserTransactionPatternViewRepository $patternViewRepository
    ) {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/transaction-pattern/",
     *   summary="Список автоматических распределений транзакций пользователя",
     *   tags={"[banking] transaction-pattern"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="destination", in="query"),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function get(FindUserTransactionPatternByDestinationRequest $findRequest): JsonResponse {
        return response()->json($this->patternViewRepository->findByUserIdWithRelations(
            $this->user->getAuthIdentifier(),
            $findRequest->string('destination')
        ));
    }
}
