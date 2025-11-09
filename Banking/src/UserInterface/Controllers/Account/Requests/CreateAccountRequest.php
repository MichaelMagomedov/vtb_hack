<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Account\Requests;

use App\Banking\Enums\AccountTypeEnum;
use App\Root\Utils\Request\FormRequest;
use Illuminate\Validation\Rule;

final class CreateAccountRequest extends FormRequest
{
    public function all($keys = null): array {
        $data = parent::all();
        $data['id'] = $this->route('id');
        return $data;
    }

    public function rules(): array {
        return [
            'id' => 'required|uuid|exists:accounts,id,deleted_at,NULL',
            'name' => 'required|string',
            'number' => 'numeric|string',
            'type' => ['required', Rule::in(array_map(fn(AccountTypeEnum $t) => $t->value, AccountTypeEnum::cases()))],
            'bank_id' => 'nullable|uuid|exists:banks,id,deleted_at,NULL',
            'currency_id' => 'nullable|uuid|exists:currencies,id,deleted_at,NULL',
        ];
    }
}

