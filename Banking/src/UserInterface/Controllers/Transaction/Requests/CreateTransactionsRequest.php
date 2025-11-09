<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction\Requests;

use App\Banking\Enums\TransactionTypeEnum;
use App\Root\Utils\Request\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTransactionsRequest extends FormRequest
{
    public function rules(): array {
        return [
            'account_id' => 'required|uuid|exists:accounts,id,deleted_at,NULL',
            'amount' => 'required|numeric',
            'short_desc' => 'required|string',
            'desc' => 'nullable|string',
            'date' => 'required|date_format:Y-m-d H:i:s',
            'type' => ['required', Rule::in(array_map(fn(TransactionTypeEnum $t) => $t->value, TransactionTypeEnum::cases()))],
            'destination' => 'nullable|string',
            'category_id' => 'nullable|uuid|exists:transaction_categories,id,deleted_at,NULL',
            'code_id' => 'nullable|uuid|exists:transaction_codes,id,deleted_at,NULL',
        ];
    }
}

