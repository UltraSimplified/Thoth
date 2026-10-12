<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveDayRequest extends FormRequest
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
            'event_note' => ['nullable', 'string', 'max:255'],
            'card_id' => ['nullable', 'integer', 'exists:cards,id'],
            'question' => ['nullable', 'string', 'max:500'],
            'interpretation_notes' => ['nullable', 'string', 'max:5000'],
            'jumper_ids' => ['array'],
            'jumper_ids.*' => ['integer', 'exists:cards,id'],
            'clarifier_ids' => ['array'],
            'clarifier_ids.*' => ['integer', 'exists:cards,id'],
        ];
    }

    /**
     * Blank text fields become null, and missing card lists become empty.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_note' => $this->filled('event_note') ? $this->input('event_note') : null,
            'card_id' => $this->filled('card_id') ? $this->input('card_id') : null,
            'question' => $this->filled('question') ? $this->input('question') : null,
            'interpretation_notes' => $this->filled('interpretation_notes') ? $this->input('interpretation_notes') : null,
            'jumper_ids' => $this->input('jumper_ids', []),
            'clarifier_ids' => $this->input('clarifier_ids', []),
        ]);
    }

    /**
     * A question, notes, jumpers or clarifiers need a main card, and no card may appear twice in a day.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasReadingDetails = $this->filled('question')
                    || $this->filled('interpretation_notes')
                    || $this->input('jumper_ids') !== []
                    || $this->input('clarifier_ids') !== [];

                if ($this->input('card_id') === null && $hasReadingDetails) {
                    $validator->errors()->add('card_id', 'Pick the main card before adding a question, notes, jumpers or clarifiers.');

                    return;
                }

                $cardIds = [
                    $this->input('card_id'),
                    ...$this->input('jumper_ids'),
                    ...$this->input('clarifier_ids'),
                ];
                $cardIds = array_filter($cardIds, fn ($cardId): bool => $cardId !== null);

                if (count($cardIds) !== count(array_unique($cardIds))) {
                    $validator->errors()->add('card_id', 'A card can appear only once in a day.');
                }
            },
        ];
    }

    /**
     * The validated morning fields in the shape DayWriter expects.
     *
     * @return array<string, mixed>
     */
    public function morningData(): array
    {
        $validated = $this->validated();

        return [
            'event_note' => $validated['event_note'] ?? null,
            'card_id' => isset($validated['card_id']) ? (int) $validated['card_id'] : null,
            'question' => $validated['question'] ?? null,
            'interpretation_notes' => $validated['interpretation_notes'] ?? null,
            'jumper_ids' => array_map('intval', $validated['jumper_ids'] ?? []),
            'clarifier_ids' => array_map('intval', $validated['clarifier_ids'] ?? []),
        ];
    }
}
