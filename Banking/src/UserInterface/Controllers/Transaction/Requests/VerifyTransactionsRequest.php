<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction\Requests;

use App\Root\Utils\Request\FormRequest;

final class VerifyTransactionsRequest extends FormRequest
{
    public function rules(): array {
        return [
            'id' => 'nullable|uuid|exists:transactions,id,deleted_at,NULL',
            'category_id' => 'nullable|uuid|exists:transaction_categories,id,deleted_at,NULL',
            'allow_empty_category' => 'nullable|boolean',
            'start_time' => 'nullable|date_format:Y-m-d H:i:s',
            'end_time' => 'nullable|date_format:Y-m-d H:i:s',
            'exclude_income' => 'nullable|boolean',
            'exclude_expense' => 'nullable|boolean'
        ];
    }
}

