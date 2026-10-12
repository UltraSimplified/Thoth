<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewImportRequest extends FormRequest
{
    /**
     * Only the signed-in owner reaches this request (the auth middleware guards the route).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:200000'],
            'year' => ['required', 'integer', 'between:1900,2100'],
        ];
    }
}
