<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\AccountBalance;

use App\Banking\Repositories\AccountBalance\AccountBalanceViewRepository;
use App\Banking\Services\AccountBalance\AccountBalanceService;
use App\Banking\Services\AccountBalance\Dto\CreateAccountBalanceDto;
use App\Banking\Services\AccountBalance\Dto\UpdateAccountBalanceDto;
use App\Banking\UserInterface\Controllers\AccountBalance\Requests\CreateAccountBalanceRequest;
use App\Banking\UserInterface\Controllers\AccountBalance\Requests\DeleteAccountBalanceRequest;
use App\Banking\UserInterface\Controllers\AccountBalance\Requests\FindAccountBalancesRequest;
use App\Banking\UserInterface\Controllers\AccountBalance\Requests\GetAccountBalanceRequest;
use App\Banking\UserInterface\Controllers\AccountBalance\Requests\ListAccountBalancesRequest;
use App\Banking\UserInterface\Controllers\AccountBalance\Requests\UpdateAccountBalanceRequest;
use App\Banking\UserInterface\Controllers\AccountBalance\Requests\UpdateAccountBalancesOrderRequest;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

final class AccountBalanceController extends Controller
{
    public function __construct(
        private readonly ?Authenticatable             $user = null,
        private readonly AccountBalanceService        $accountBalanceService,
        private readonly AccountBalanceViewRepository $accountBalanceViewRepository
    ) {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/account-balance/",
     *   summary="Список корректировок балансов",
     *   tags={"[banking] account-balance"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="page", in="query", required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function find(FindAccountBalancesRequest $findRequest): JsonResponse {
        $accountBalances = $this->accountBalanceViewRepository->findByUserIdWithRelations(
            $this->user->getAuthIdentifier(),
        );
        return response()->json($accountBalances);
    }


    /**
     * @OA\Get(
     *   path="/api/v1/banking/account-balance/list",
     *   summary="Список корректировок балансов (без пагинации)",
     *   tags={"[banking] account-balance"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="from", in="query"),
     *   @OA\Parameter(name="to", in="query"),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function list(ListAccountBalancesRequest $listRequest): JsonResponse {
        $accountBalances = $this->accountBalanceViewRepository->findAllByUserIdWithRelations(
            $this->user->getAuthIdentifier(),
            $listRequest->dateTime('from'),
            $listRequest->dateTime('to')
        );
        return response()->json($accountBalances);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/account-balance/{id}",
     *   summary="Получить корректировку счета",
     *   tags={"[banking] account-balance"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function get(GetAccountBalanceRequest $getAccountBalanceRequest): JsonResponse {
        $this->authorize('account-balance', $getAccountBalanceRequest->route('id'));
        $accountBalance = $this->accountBalanceViewRepository->findByIdWithRelations(
            $getAccountBalanceRequest->route('id')
        );
        return response()->json($accountBalance);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/banking/account-balance/{id}",
     *   summary="Удалить корректировку счета",
     *   tags={"[banking] account-balance"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function delete(DeleteAccountBalanceRequest $deleteAccountBalanceRequest): JsonResponse {
        $this->authorize('account-balance', $deleteAccountBalanceRequest->route('id'));
        $this->accountBalanceService->delete($deleteAccountBalanceRequest->route('id'));
        return response()->json(['success' => true]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/banking/account-balance/order",
     *   summary="Обновить порядок корректировок балансов",
     *   tags={"[banking] account-balance"},
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
    public function updateOrder(UpdateAccountBalancesOrderRequest $updateOrderRequest): JsonResponse {
        foreach ($updateOrderRequest->input('ids') as $id) {
            $this->authorize('account-balance', $id);
        }
        $this->accountBalanceService->updateOrder($updateOrderRequest->input('ids'));
        return response()->json(['success' => true]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/banking/account-balance/{id}",
     *   summary="Обновить корректировку счета",
     *   tags={"[banking] account-balance"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"balance", "balance_date"},
     *         @OA\Property(property="balance", type="float"),
     *         @OA\Property(property="balance_date", type="string"),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function update(UpdateAccountBalanceRequest $updateRequests): JsonResponse {
        $this->authorize('account-balance', $updateRequests->route('id'));

        $accountBalance = $this->accountBalanceService->update(new UpdateAccountBalanceDto(
            $updateRequests->route('id'),
            $updateRequests->float('balance'),
            $updateRequests->dateTime('balance_date'),
        ));
        return response()->json(
            $this->accountBalanceViewRepository->findByIdWithRelations($accountBalance->getId())
        );
    }

    /**
     * @OA\Post(
     *   path="/api/v1/banking/account-balance/",
     *   summary="Создать корректировку счета",
     *   tags={"[banking] account-balance"},
     *   security={{"bearer": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"balance", "balance_date"},
     *         @OA\Property(property="balance", type="float"),
     *         @OA\Property(property="balance_date", type="string"),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function create(CreateAccountBalanceRequest $createRequests): JsonResponse {
        $accountBalance = $this->accountBalanceService->create(new CreateAccountBalanceDto(
            $createRequests->float('balance'),
            $createRequests->dateTime('balance_date'),
            $this->user->getAuthIdentifier(),
        ));
        return response()->json(
            $this->accountBalanceViewRepository->findByIdWithRelations($accountBalance->getId())
        );
    }
}
