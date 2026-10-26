<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_id'  => ['required', 'integer', 'exists:staff,id'],
            'starts_at' => ['required', 'date', 'after:now'],
        ];
    }
}
