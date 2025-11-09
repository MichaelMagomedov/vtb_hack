<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\Transaction\Requests;

use App\Root\Utils\Request\FormRequest;

final class GetDestinationAutocompleteRequest extends FormRequest
{
    public function rules(): array {
        return [
            'query' => 'nullable|string',
        ];
    }
}

