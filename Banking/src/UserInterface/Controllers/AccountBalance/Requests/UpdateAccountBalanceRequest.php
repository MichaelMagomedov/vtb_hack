<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\AccountBalance\Requests;

use App\Root\Utils\Request\FormRequest;

final class UpdateAccountBalanceRequest extends FormRequest
{
    public function all($keys = null): array {
        $data = parent::all();
        $data['id'] = $this->route('id');
        return $data;
    }

    public function rules(): array {
        return [
            'id' => 'required|uuid|exists:account_balances,id,deleted_at,NULL',
            'balance' => 'required|numeric',
            'balance_date' => 'required|date_format:Y-m-d',
        ];
    }
}

