<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Day;
use App\Models\ReadingCard;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the backup and the two CSV exports (spec 4.11): days with their readings, jumpers, clarifiers and
 * themes, and card notes. Card slugs identify cards, so a backup restores onto any copy of the same deck.
 */
final class BackupExporter
{
    public const VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'app' => config('app.name'),
            'version' => self::VERSION,
            'exported_at' => CarbonImmutable::now()->toIso8601String(),
            'days' => $this->days()->map(fn (Day $day): array => $this->dayToArray($day))->values()->all(),
            'card_notes' => Card::query()
                ->whereNotNull('personal_note')
                ->orderBy('slug')
                ->get(['slug', 'personal_note'])
                ->map(fn (Card $card): array => ['card' => $card->slug, 'note' => $card->personal_note])
                ->values()
                ->all(),
        ];
    }

    /**
     * One row per day, with the card, the notes, the jumpers and clarifiers joined by "|", and the themes joined by "; ".
     */
    public function daysCsv(): string
    {
        $rows = [[
            'date', 'event_note', 'outcome_note', 'resonated', 'card', 'question',
            'interpretation_notes', 'jumpers', 'clarifiers', 'themes',
        ]];

        foreach ($this->days() as $day) {
            $reading = $day->reading;

            $rows[] = [
                $day->date->toDateString(),
                $day->event_note,
                $day->outcome_note,
                $day->resonated === null ? '' : ($day->resonated ? 'yes' : 'no'),
                $reading?->card->slug,
                $reading?->question,
                $reading?->interpretation_notes,
                $reading === null ? '' : $reading->jumpers->map(fn (ReadingCard $entry): string => $entry->card->slug)->implode('|'),
                $reading === null ? '' : $reading->clarifiers->map(fn (ReadingCard $entry): string => $entry->card->slug)->implode('|'),
                $day->themes->pluck('name')->sort()->implode('; '),
            ];
        }

        return $this->csv($rows);
    }

    public function cardNotesCsv(): string
    {
        $rows = [['card', 'name', 'note']];

        foreach (Card::query()->whereNotNull('personal_note')->orderBy('slug')->get(['slug', 'name', 'personal_note']) as $card) {
            $rows[] = [$card->slug, $card->name, $card->personal_note];
        }

        return $this->csv($rows);
    }

    /**
     * @return Collection<int, Day>
     */
    private function days(): Collection
    {
        return Day::query()
            ->with(['reading.card', 'reading.jumpers.card', 'reading.clarifiers.card', 'themes'])
            ->orderBy('date')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function dayToArray(Day $day): array
    {
        $reading = $day->reading;

        return [
            'date' => $day->date->toDateString(),
            'event_note' => $day->event_note,
            'outcome_note' => $day->outcome_note,
            'resonated' => $day->resonated,
            'themes' => $day->themes->pluck('name')->sort()->values()->all(),
            'reading' => $reading === null ? null : [
                'card' => $reading->card->slug,
                'question' => $reading->question,
                'interpretation_notes' => $reading->interpretation_notes,
                'jumpers' => $reading->jumpers->map(fn (ReadingCard $entry): string => $entry->card->slug)->values()->all(),
                'clarifiers' => $reading->clarifiers->map(fn (ReadingCard $entry): string => $entry->card->slug)->values()->all(),
            ],
        ];
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Could not open a temporary stream for the CSV export.');
        }

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (mixed $value): string => (string) $value, $row), ',', '"', '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
