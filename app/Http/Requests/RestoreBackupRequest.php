<?php

namespace App\Http\Requests;

use App\Services\BackupExporter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RestoreBackupRequest extends FormRequest
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
            'backup' => ['required', 'file', 'max:20480', 'mimetypes:application/json,text/plain'],
        ];
    }

    /**
     * The uploaded backup, decoded and checked, with every optional key filled in.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function backup(): array
    {
        $decoded = json_decode((string) file_get_contents($this->file('backup')->getRealPath()), true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages(['backup' => 'This file is not a Thoth backup.']);
        }

        $validated = Validator::make($decoded, [
            'version' => ['required', Rule::in([BackupExporter::VERSION])],
            'days' => ['present', 'array'],
            'days.*.date' => ['required', 'date_format:Y-m-d'],
            'days.*.event_note' => ['nullable', 'string', 'max:255'],
            'days.*.outcome_note' => ['nullable', 'string', 'max:255'],
            'days.*.resonated' => ['nullable', 'boolean'],
            'days.*.themes' => ['nullable', 'array'],
            'days.*.themes.*' => ['string', 'max:60'],
            'days.*.reading' => ['nullable', 'array'],
            'days.*.reading.card' => ['required_with:days.*.reading', 'string'],
            'days.*.reading.question' => ['nullable', 'string', 'max:500'],
            'days.*.reading.interpretation_notes' => ['nullable', 'string', 'max:5000'],
            'days.*.reading.jumpers' => ['nullable', 'array'],
            'days.*.reading.jumpers.*' => ['string'],
            'days.*.reading.clarifiers' => ['nullable', 'array'],
            'days.*.reading.clarifiers.*' => ['string'],
            'card_notes' => ['nullable', 'array'],
            'card_notes.*.card' => ['required', 'string'],
            'card_notes.*.note' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return [
            'days' => array_map(fn (array $day): array => [
                'date' => $day['date'],
                'event_note' => $day['event_note'] ?? null,
                'outcome_note' => $day['outcome_note'] ?? null,
                'resonated' => $day['resonated'] ?? null,
                'themes' => $day['themes'] ?? [],
                'reading' => isset($day['reading']) ? [
                    'card' => $day['reading']['card'],
                    'question' => $day['reading']['question'] ?? null,
                    'interpretation_notes' => $day['reading']['interpretation_notes'] ?? null,
                    'jumpers' => $day['reading']['jumpers'] ?? [],
                    'clarifiers' => $day['reading']['clarifiers'] ?? [],
                ] : null,
            ], $validated['days']),
            'card_notes' => array_map(fn (array $note): array => [
                'card' => $note['card'],
                'note' => $note['note'] ?? null,
            ], $validated['card_notes'] ?? []),
        ];
    }
}
