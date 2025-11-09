<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Account\Requests;

use App\Root\Utils\Request\FormRequest;

final class UpdateAccountOrderRequest extends FormRequest
{
    public function rules(): array {
        return [
            'ids.*' => 'required|uuid|exists:accounts,id,deleted_at,NULL',
        ];
    }
}

