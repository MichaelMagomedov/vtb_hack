<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction\Requests;

use App\Root\Utils\Request\FormRequest;

final class UpdateTransactionsOrderRequest extends FormRequest
{
    public function rules(): array {
        return [
            'ids.*' => 'required|uuid|exists:transactions,id,deleted_at,NULL',
        ];
    }
}

