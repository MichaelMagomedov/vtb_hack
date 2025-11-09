<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionCode\Requests;

use App\Root\Utils\Request\FormRequest;

final class FindTransactionCodesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => 'nullable|uuid|exists:transaction_categories,id,deleted_at,NULL',
            'query' => 'nullable|string',
            'page' => 'required|numeric',
        ];
    }
}

