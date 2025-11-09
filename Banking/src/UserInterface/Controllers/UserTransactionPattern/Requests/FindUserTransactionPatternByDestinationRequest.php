<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\UserTransactionPattern\Requests;

use App\Root\Utils\Request\FormRequest;

final class FindUserTransactionPatternByDestinationRequest extends FormRequest
{
    public function rules(): array {
        return [
            'destination' => 'required',
        ];
    }
}

