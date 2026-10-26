<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id'       => ['required', 'integer', 'exists:services,id'],
            'staff_id'         => ['required', 'integer', 'exists:staff,id'],
            'starts_at'        => ['required', 'date', 'after:now'],
            'customer_name'    => ['required', 'string', 'max:100'],
            'customer_phone'   => ['required', 'string', 'max:20'],
            'customer_email'   => ['nullable', 'email', 'max:150'],
            'note'             => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['source' => 'online']);
    }
}
