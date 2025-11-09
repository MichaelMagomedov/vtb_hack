<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\AccountBalance\Requests;

use App\Root\Utils\Request\FormRequest;

final class GetAccountBalanceRequest extends FormRequest
{
    public function all($keys = null): array {
        $data = parent::all();
        $data['id'] = $this->route('id');
        return $data;
    }

    public function rules(): array {
        return [
            'id' => 'required|uuid|exists:account_balances,id,deleted_at,NULL',
        ];
    }
}

