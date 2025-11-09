<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\AccountBalance\Requests;

use App\Root\Utils\Request\FormRequest;

final class UpdateAccountBalancesOrderRequest extends FormRequest
{
    public function rules(): array {
        return [
            'ids.*' => 'required|uuid|exists:account_balances,id,deleted_at,NULL',
        ];
    }
}

