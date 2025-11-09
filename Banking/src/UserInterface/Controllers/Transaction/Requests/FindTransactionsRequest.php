<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction\Requests;

use App\Banking\Enums\TransactionTypeEnum;
use App\Root\Utils\Request\FormRequest;
use Illuminate\Validation\Rule;

final class FindTransactionsRequest extends FormRequest
{
    /**
     * добавил фильтр тут, добавь его и сюда @see VerifyTransactionsRequest
     */
    public function rules(): array {
        return [
            'query' => 'nullable|string',
            'exclude_types' => 'nullable|array',
            'exclude_types.*' => [Rule::in(array_map(fn(TransactionTypeEnum $type) => $type->value, TransactionTypeEnum::cases()))],
            'page' => 'required|numeric',
            'start_time' => 'nullable|date_format:Y-m-d H:i:s',
            'end_time' => 'nullable|date_format:Y-m-d H:i:s',
            'category_id' => 'nullable|uuid|exists:transaction_categories,id,deleted_at,NULL',
            'allow_empty_category' => 'nullable|in:true,false',
            'exclude_income' => 'nullable|in:true,false',
            'exclude_expense' => 'nullable|in:true,false'
        ];
    }

}

