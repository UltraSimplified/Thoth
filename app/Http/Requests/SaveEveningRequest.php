<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveEveningRequest extends FormRequest
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
            'outcome_note' => ['nullable', 'string', 'max:255'],
            'resonated' => ['nullable', 'boolean'],
            'themes' => ['array'],
            'themes.*' => ['string', 'max:60'],
        ];
    }

    /**
     * The validated evening fields in the shape DayWriter expects.
     *
     * @return array{outcome_note: ?string, resonated: ?bool, themes: list<string>}
     */
    public function eveningData(): array
    {
        $validated = $this->validated();

        return [
            'outcome_note' => filled($validated['outcome_note'] ?? null) ? $validated['outcome_note'] : null,
            'resonated' => isset($validated['resonated']) ? (bool) $validated['resonated'] : null,
            'themes' => array_values(array_filter(
                array_map(trim(...), $validated['themes'] ?? []),
                fn (string $theme): bool => $theme !== '',
            )),
        ];
    }
}
