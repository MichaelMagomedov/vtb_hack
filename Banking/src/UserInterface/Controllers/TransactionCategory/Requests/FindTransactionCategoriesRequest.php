<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionCategory\Requests;

use App\Root\Utils\Request\FormRequest;

final class FindTransactionCategoriesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code_id' => 'nullable|uuid|exists:transaction_codes,id,deleted_at,NULL',
            'query' => 'nullable|string',
            'page' => 'required|numeric',
        ];
    }

}

