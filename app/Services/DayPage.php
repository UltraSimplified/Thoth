<?php

namespace App\Services;

use App\Models\Day;
use Carbon\CarbonImmutable;

/**
 * Builds the props shared by the Today screen, the day page and the day edit page.
 */
final class DayPage
{
    private readonly TreePlacement $tree;

    public function __construct(
        private readonly AppSettings $settings,
        private readonly CardCatalog $cards,
    ) {
        $this->tree = new TreePlacement($this->settings->treePlacement());
    }

    /**
     * @return array<string, mixed>
     */
    public function props(CarbonImmutable $date): array
    {
        $day = Day::query()
            ->with(['reading.card', 'reading.jumpers.card', 'reading.clarifiers.card', 'themes'])
            ->where('date', $date->toDateString())
            ->first();

        $today = today();
        $lockDay = $day ?? new Day(['date' => $date]);
        $birthRoles = $this->cards->birthRoles();

        return [
            'date' => $date->toDateString(),
            'dateLabel' => $date->translatedFormat('l j F Y'),
            'isToday' => $date->isSameDay($today),
            'day' => $day === null ? null : $this->dayData($day, $birthRoles),
            'locks' => [
                'morning' => $lockDay->isLockedOn($today),
                'morningUntil' => $lockDay->editableUntil(Day::MORNING_EDIT_DAYS),
            ],
            'cards' => $this->cards->all(),
            'tree' => $this->treeData($day),
            'journalUrl' => 'https://750words.com/account/stats/'.$date->toDateString(),
            'showJournalReminder' => ! $this->settings->journalTimezoneConfirmed(),
        ];
    }

    /**
     * The card, jumpers and clarifiers of a day as they appear on the page.
     *
     * @param  array<int, array<int, string>>  $birthRoles
     * @return array<string, mixed>
     */
    private function dayData(Day $day, array $birthRoles): array
    {
        $reading = $day->reading;

        return [
            'id' => $day->id,
            'eventNote' => $day->event_note,
            'outcomeNote' => $day->outcome_note,
            'resonated' => $day->resonated,
            'themes' => $day->themes->pluck('name')->sort()->values(),
            'reading' => $reading === null ? null : [
                'question' => $reading->question,
                'interpretationNotes' => $reading->interpretation_notes,
                'card' => $this->cards->present($reading->card, $birthRoles),
                'jumpers' => $reading->jumpers->map(fn ($entry) => $this->cards->present($entry->card, $birthRoles))->values(),
                'clarifiers' => $reading->clarifiers->map(fn ($entry) => $this->cards->present($entry->card, $birthRoles))->values(),
            ],
        ];
    }

    /**
     * Highlights for the compact Tree on the day page: the main card lit, jumpers and clarifiers faint,
     * and the birth positions outlined. Also each highlighted element, named, for the text key beside the tree.
     *
     * @return array<string, mixed>
     */
    private function treeData(?Day $day): array
    {
        $reading = $day?->reading;

        $elements = [];

        if ($reading !== null) {
            $elements[] = $this->tree->elementFor($reading->card, 'main', 'Main card');

            foreach ($reading->jumpers as $entry) {
                $elements[] = $this->tree->elementFor($entry->card, 'jumper', 'Jumper');
            }

            foreach ($reading->clarifiers as $entry) {
                $elements[] = $this->tree->elementFor($entry->card, 'clarifier', 'Clarifier');
            }
        }

        return [
            'main' => $this->tree->highlightsFor($reading ? [$reading->card] : []),
            'jumpers' => $this->tree->highlightsFor($reading?->jumpers->pluck('card') ?? []),
            'clarifiers' => $this->tree->highlightsFor($reading?->clarifiers->pluck('card') ?? []),
            'birth' => $this->tree->highlightsFor(array_column($this->cards->birthEntries(), 'card')),
            'elements' => [...$elements, ...$this->cards->birthElements($this->tree)],
        ];
    }
}
