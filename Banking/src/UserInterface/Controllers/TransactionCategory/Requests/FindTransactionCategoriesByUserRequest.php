<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionCategory\Requests;

use App\Root\Utils\Request\FormRequest;

final class FindTransactionCategoriesByUserRequest extends FormRequest
{
    public function rules(): array {
        return [
            'from' => 'required|date_format:Y-m-d H:i:s',
            'to' => 'required|date_format:Y-m-d H:i:s',
        ];
    }
}

