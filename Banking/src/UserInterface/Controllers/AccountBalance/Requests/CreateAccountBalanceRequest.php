<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\AccountBalance\Requests;

use App\Root\Utils\Request\FormRequest;

final class CreateAccountBalanceRequest extends FormRequest
{
    public function rules(): array {
        return [
            'balance' => 'required|numeric',
            'balance_date' => 'required|date_format:Y-m-d',
        ];
    }
}

