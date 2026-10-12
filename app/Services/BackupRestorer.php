<?php

namespace App\Services;

use App\Models\Card;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Restores a backup made by BackupExporter. Days in the backup replace the same dates here; days not in the
 * backup are left alone. Every card slug is checked before anything is written.
 */
final class BackupRestorer
{
    public function __construct(
        private readonly DayWriter $writer,
    ) {}

    /**
     * @param  array<string, mixed>  $backup  A validated backup (see RestoreBackupRequest)
     * @return array{days: int, card_notes: int}
     */
    public function restore(array $backup): array
    {
        $cardIds = Card::query()->pluck('id', 'slug')->all();
        $this->ensureEveryCardExists($backup, $cardIds);

        return DB::transaction(function () use ($backup, $cardIds): array {
            $days = 0;

            foreach ($backup['days'] as $entry) {
                $this->restoreDay($entry, $cardIds);
                $days++;
            }

            foreach ($backup['card_notes'] as $entry) {
                Card::query()
                    ->whereKey($cardIds[$entry['card']])
                    ->update(['personal_note' => $entry['note']]);
            }

            return ['days' => $days, 'card_notes' => count($backup['card_notes'])];
        });
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, int>  $cardIds
     */
    private function restoreDay(array $entry, array $cardIds): void
    {
        $date = CarbonImmutable::parse($entry['date'])->startOfDay();
        $reading = $entry['reading'] ?? null;

        $this->writer->saveMorning($date, [
            'event_note' => $entry['event_note'] ?? null,
            'card_id' => $reading === null ? null : $cardIds[$reading['card']],
            'question' => $reading['question'] ?? null,
            'interpretation_notes' => $reading['interpretation_notes'] ?? null,
            'jumper_ids' => $reading === null ? [] : array_map(fn (string $slug): int => $cardIds[$slug], $reading['jumpers']),
            'clarifier_ids' => $reading === null ? [] : array_map(fn (string $slug): int => $cardIds[$slug], $reading['clarifiers']),
        ]);

        $this->writer->saveEvening($date, [
            'outcome_note' => $entry['outcome_note'] ?? null,
            'resonated' => $entry['resonated'] ?? null,
            'themes' => $entry['themes'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $backup
     * @param  array<string, int>  $cardIds
     */
    private function ensureEveryCardExists(array $backup, array $cardIds): void
    {
        $slugs = [];

        foreach ($backup['days'] as $entry) {
            if ($entry['reading'] !== null) {
                $slugs[] = $entry['reading']['card'];
                array_push($slugs, ...$entry['reading']['jumpers'], ...$entry['reading']['clarifiers']);
            }
        }

        foreach ($backup['card_notes'] as $entry) {
            $slugs[] = $entry['card'];
        }

        $missing = array_values(array_unique(array_filter(
            $slugs,
            fn (string $slug): bool => ! array_key_exists($slug, $cardIds),
        )));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'backup' => 'This backup names cards this deck does not have: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
