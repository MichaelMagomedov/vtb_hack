<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Bank;

use App\Banking\Repositories\Bank\BankViewRepository;
use App\Banking\UserInterface\Controllers\Bank\Requetst\FindBanksRequest;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

final class BankController extends Controller
{
    public function __construct(
        private readonly BankViewRepository $bankViewRepository
    ) {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/bank/",
     *   summary="Список банков",
     *   tags={"[banking] bank"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="query", in="query"),
     *   @OA\Parameter(name="page", in="query", required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function list(FindBanksRequest $banksRequest): JsonResponse {
        return response()->json($this->bankViewRepository->findAllWithRelations(
            $banksRequest->string('query')
        ));
    }

}
