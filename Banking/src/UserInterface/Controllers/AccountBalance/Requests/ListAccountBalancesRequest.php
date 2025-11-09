<?php

declare(strict_types=1);

namespace App\Banking\UserInterface\Controllers\AccountBalance\Requests;

use App\Root\Utils\Request\FormRequest;

/**
 * Это тоже самое но без пагинаци
 */
final class ListAccountBalancesRequest extends FormRequest
{
    public function rules(): array {
        return [
            'start_time' => 'nullable|date_format:Y-m-d',
            'end_time' => 'nullable|date_format:Y-m-d',
        ];
    }

}

