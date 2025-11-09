<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction\Requests;

use App\Banking\Enums\TransactionTypeEnum;
use App\Root\Utils\Request\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTransactionsRequest extends FormRequest
{
    public function all($keys = null): array {
        $data = parent::all();
        $data['id'] = $this->route('id');
        return $data;
    }

    public function rules(): array {
        return [
            'id' => 'required|uuid|exists:transactions,id,deleted_at,NULL',
            'account_id' => 'required|uuid|exists:accounts,id,deleted_at,NULL',
            'amount' => 'required|numeric',
            'short_desc' => 'required|string',
            'date' => 'required|date_format:Y-m-d H:i:s',
            'type' => ['required', Rule::in(array_map(fn(TransactionTypeEnum $t) => $t->value, TransactionTypeEnum::cases()))],
            'desc' => 'nullable|string',
            'destination' => 'nullable|string',
            'category_id' => 'nullable|uuid|exists:transaction_categories,id,deleted_at,NULL',
            'code_id' => 'nullable|uuid|exists:transaction_codes,id,deleted_at,NULL',
            'transaction_pattern' => 'nullable',
            'transaction_pattern.id' => 'nullable|uuid|exists:user_transaction_patterns,id,deleted_at,NULL',
            'transaction_pattern.category_id' => 'nullable|uuid|exists:transaction_categories,id,deleted_at,NULL',
            'transaction_pattern.code_id' => 'nullable|uuid|exists:transaction_codes,id,deleted_at,NULL'

        ];
    }
}

