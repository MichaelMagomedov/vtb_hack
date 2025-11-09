<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionParse\Requests;

use App\Root\Utils\Request\FormRequest;

final class ParseTransactionsHtmlRequest extends FormRequest
{
    public function rules(): array {
        return [
            'account_id' => 'required|uuid|exists:accounts,id,deleted_at,NULL',
            'html' => 'required|max:15000',
        ];
    }
}

