<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Currency;

use App\Banking\Repositories\Currency\CurrencyViewRepository;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

final class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyViewRepository $currencyViewRepository
    ) {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/currency/",
     *   summary="Список валют",
     *   tags={"[banking] currency"},
     *   security={{"bearer": {}}},
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function list(): JsonResponse {
        return response()->json($this->currencyViewRepository->findAllWithRelations());
    }

}
