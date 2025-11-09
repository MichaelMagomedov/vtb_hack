<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Bank\Requetst;

use App\Root\Utils\Request\FormRequest;

final class FindBanksRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => 'nullable|string',
            'page' => 'required|numeric',
        ];
    }

}

