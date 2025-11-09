<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\AccountBalance\Requests;

use App\Root\Utils\Request\FormRequest;

final class FindAccountBalancesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => 'required|numeric',
            'account_id' => 'nullable|uuid|exists:accounts,id,deleted_at,NULL',
        ];
    }

}

