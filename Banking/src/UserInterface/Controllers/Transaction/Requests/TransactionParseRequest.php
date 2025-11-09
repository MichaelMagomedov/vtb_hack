<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction\Requests;

use App\Root\Utils\Request\FormRequest;

final class TransactionParseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required|string',
            'file_id' => 'required|string',
        ];
    }
}

