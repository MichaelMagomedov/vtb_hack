<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Account;

use App\Banking\Enums\AccountTypeEnum;
use App\Banking\Repositories\Account\AccountViewRepository;
use App\Banking\Services\Account\AccountService;
use App\Banking\Services\Account\Dto\UpdateAccountDto;
use App\Banking\UserInterface\Controllers\Account\Requests\DeleteAccountRequest;
use App\Banking\UserInterface\Controllers\Account\Requests\GetAccountRequest;
use App\Banking\UserInterface\Controllers\Account\Requests\UpdateAccountOrderRequest;
use App\Banking\UserInterface\Controllers\Account\Requests\UpdateAccountRequest;
use App\Root\UserInterface\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

final class AccountController extends Controller
{
    public function __construct(
        private readonly ?Authenticatable      $user = null,
        private readonly AccountService        $accountService,
        private readonly AccountViewRepository $accountViewRepository,
    ) {
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/account/",
     *   summary="Список счетов пользователя",
     *   tags={"[banking] account"},
     *   security={{"bearer": {}}},
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function list(): JsonResponse {
        $accounts = $this->accountViewRepository->findByUserIdWithRelations(
            $this->user->getAuthIdentifier()
        );
        return response()->json($accounts);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/banking/account/{id}",
     *   summary="Получить информацию о счете",
     *   tags={"[banking] account"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function get(GetAccountRequest $getAccountRequest): JsonResponse {
        $this->authorize('account', $getAccountRequest->route('id'));
        $account = $this->accountViewRepository->findByIdWithRelations(
            $getAccountRequest->route('id')
        );
        return response()->json($account);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/banking/account/{id}",
     *   summary="Обновить счет",
     *   tags={"[banking] account"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         required={"name", "type", "number"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="number", type="integer"),
     *         @OA\Property(property="type", type="string", enum={"debit", "credit"}),
     *         @OA\Property(property="currency_id", type="string"),
     *         @OA\Property(property="bank_id", type="string"),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function update(UpdateAccountRequest $updateRequests): JsonResponse {
        $this->authorize('account', $updateRequests->route('id'));
        $account = $this->accountService->update(new UpdateAccountDto(
            $updateRequests->route('id'),
            $updateRequests->string('name'),
            AccountTypeEnum::from($updateRequests->string('type')),
            $updateRequests->integer('number'),
            $updateRequests->string('bank_id'),
            $updateRequests->string('currency_id'),
        ));
        return response()->json(
            $this->accountViewRepository->findByIdWithRelations($account->getId())
        );
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/banking/account/{id}",
     *   summary="Удалить счет",
     *   tags={"[banking] account"},
     *   security={{"bearer": {}}},
     *   @OA\Parameter(name="id", in="path" ,required=true),
     *   @OA\Response(response="200", description="Успешный ответ", @OA\JsonContent()),
     *   @OA\Response(response="500", description="Ошибка сервера", @OA\JsonContent()),
     *   @OA\Response(response="400", description="Ошибка валидации", @OA\JsonContent()),
     * )
     */
    public function delete(DeleteAccountRequest $deleteAccountRequest): JsonResponse {
        $this->authorize('account', $deleteAccountRequest->route('id'));
        $this->accountService->delete($deleteAccountRequest->route('id'));
        return response()->json(['success' => true]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/banking/account/order",
     *   summary="Обновить порядок счетов",
     *   tags={"[banking] account"},
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
    public function updateOrder(UpdateAccountOrderRequest $updateOrderRequest): JsonResponse {
        foreach ($updateOrderRequest->input('ids') as $id) {
            $this->authorize('account', $id);
        }
        $this->accountService->updateOrder($updateOrderRequest->input('ids'));
        return response()->json(['success' => true]);
    }
}
