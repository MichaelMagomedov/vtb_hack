<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\TransactionParse\Requests;

use App\Root\Utils\Request\FormRequest;

final class ParseTransactionsRequest extends FormRequest
{
    public function rules(): array {
        return [
            'file_id' => 'required',
            'chat_id' => 'required',
            'username' => 'required',
        ];
    }
}

