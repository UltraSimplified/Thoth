<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    /**
     * Only the signed-in owner reaches this request (the auth middleware guards the route).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the rows the owner confirmed on the review screen are sent, one per date.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'min:1', 'max:1000'],
            'days.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'days.*.card_id' => ['nullable', 'integer', 'exists:cards,id'],
            'days.*.jumper_ids' => ['array'],
            'days.*.jumper_ids.*' => ['integer', 'exists:cards,id'],
            'days.*.clarifier_ids' => ['array'],
            'days.*.clarifier_ids.*' => ['integer', 'exists:cards,id'],
            'days.*.interpretation_notes' => ['nullable', 'string', 'max:5000'],
            'days.*.event_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The confirmed rows in the shape DayWriter expects, keyed by date.
     *
     * @return array<int, array<string, mixed>>
     */
    public function confirmedDays(): array
    {
        /** @var list<array<string, mixed>> $days */
        $days = $this->validated('days');

        return collect($days)
            ->map(fn (array $day): array => [
                'date' => $day['date'],
                'event_note' => filled($day['event_note'] ?? null) ? $day['event_note'] : null,
                'card_id' => isset($day['card_id']) ? (int) $day['card_id'] : null,
                'question' => null,
                'interpretation_notes' => filled($day['interpretation_notes'] ?? null) ? $day['interpretation_notes'] : null,
                'jumper_ids' => array_map('intval', $day['jumper_ids'] ?? []),
                'clarifier_ids' => array_map('intval', $day['clarifier_ids'] ?? []),
            ])
            ->values()
            ->all();
    }
}
