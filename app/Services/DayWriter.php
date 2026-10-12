<?php

namespace App\Services;

use App\Models\Day;
use App\Models\Reading;
use App\Models\ReadingCard;
use App\Models\Theme;
use App\ReadingCardKind;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Saves the morning and evening parts of a day. Callers authorise the write first (see DayPolicy).
 */
final class DayWriter
{
    /**
     * Save the morning fields for a date, replacing the reading's card, question, notes, jumpers and
     * clarifiers. Without a main card the reading is removed, which leaves a note-only day.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveMorning(CarbonImmutable $date, array $data): Day
    {
        return DB::transaction(function () use ($date, $data): Day {
            $day = Day::query()->firstOrCreate(['date' => $date->toDateString()]);
            $day->update(['event_note' => $data['event_note']]);

            if ($data['card_id'] === null) {
                Reading::query()->where('day_id', $day->id)->delete();
            } else {
                $reading = Reading::query()->updateOrCreate(
                    ['day_id' => $day->id],
                    [
                        'card_id' => $data['card_id'],
                        'question' => $data['question'],
                        'interpretation_notes' => $data['interpretation_notes'],
                    ],
                );

                ReadingCard::query()->where('reading_id', $reading->id)->delete();

                $this->storeCards($reading, ReadingCardKind::Jumper, $data['jumper_ids']);
                $this->storeCards($reading, ReadingCardKind::Clarifier, $data['clarifier_ids']);
            }

            return $this->removeIfEmpty($day->refresh());
        });
    }

    /**
     * Save the outcome, resonance and themes for a date. Themes are matched by name, creating any new ones.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveEvening(CarbonImmutable $date, array $data): Day
    {
        return DB::transaction(function () use ($date, $data): Day {
            $day = Day::query()->firstOrCreate(['date' => $date->toDateString()]);
            $day->update([
                'outcome_note' => $data['outcome_note'],
                'resonated' => $data['resonated'],
            ]);

            /** @var list<string> $themes */
            $themes = $data['themes'];

            $themeIds = collect($themes)
                ->map(fn (string $name): int => Theme::findOrCreateByName($name)->id)
                ->unique()
                ->values()
                ->all();

            $day->themes()->sync($themeIds);

            return $this->removeIfEmpty($day->refresh());
        });
    }

    /**
     * @param  list<int>  $cardIds
     */
    private function storeCards(Reading $reading, ReadingCardKind $kind, array $cardIds): void
    {
        foreach ($cardIds as $index => $cardId) {
            ReadingCard::query()->create([
                'reading_id' => $reading->id,
                'card_id' => $cardId,
                'kind' => $kind,
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * A day with nothing left in it is deleted, so the calendar never shows an empty day.
     */
    private function removeIfEmpty(Day $day): Day
    {
        $isEmpty = $day->event_note === null
            && $day->outcome_note === null
            && $day->resonated === null
            && ! $day->reading()->exists()
            && ! $day->themes()->exists();

        if ($isEmpty) {
            $day->delete();
        }

        return $day;
    }
}
