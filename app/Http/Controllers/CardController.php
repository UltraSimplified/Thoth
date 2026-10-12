<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Day;
use App\Models\Reading;
use App\Models\ReadingCard;
use App\Services\AppSettings;
use App\Services\CardCatalog;
use App\Services\TreePlacement;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    /**
     * The card index, grouped by suit on the page.
     */
    public function index(CardCatalog $catalog): Response
    {
        return Inertia::render('Cards', [
            'cards' => $catalog->all(),
        ]);
    }

    /**
     * A card's page: its attributions, its place on the Tree, its personal note and every day it appeared.
     */
    public function show(Card $card, CardCatalog $catalog, AppSettings $settings): Response
    {
        $placement = new TreePlacement($settings->treePlacement());

        return Inertia::render('CardShow', [
            'card' => $catalog->present($card),
            'position' => $this->position($card, $placement),
            'tree' => [
                'highlight' => $placement->highlightsFor([$card]),
                'positions' => $placement->cardsByPosition(Card::query()->get()),
            ],
            'appearances' => $this->appearances($card),
        ]);
    }

    /**
     * Save the personal note as the owner types. The change applies to every day the card appears on.
     */
    public function updateNote(Request $request, Card $card): HttpResponse
    {
        $validated = $request->validate([
            'personal_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $card->update(['personal_note' => $validated['personal_note'] ?? null]);

        return response()->noContent();
    }

    /**
     * Where the card sits: a sephira, or a path with its number and Hebrew letter.
     *
     * @return array<string, mixed>
     */
    private function position(Card $card, TreePlacement $placement): array
    {
        $position = $placement->positionFor($card);

        if ($position['sephira'] !== null) {
            return [
                'kind' => 'sephira',
                'sephira' => $position['sephira']->value,
                'label' => $position['sephira']->label(),
                'number' => $position['sephira']->number(),
                'pillar' => $position['sephira']->pillar(),
                'triad' => $position['sephira']->triad(),
            ];
        }

        $path = TreePlacement::PATHS[$position['path']] ?? throw new \LogicException('Every card sits on a sephira or a path.');

        return [
            'kind' => 'path',
            'number' => $position['path'],
            'letter' => $path['letter'],
            'from' => $path['from']->label(),
            'to' => $path['to']->label(),
        ];
    }

    /**
     * Every day the card appeared on, as the main card, a jumper or a clarifier, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function appearances(Card $card): array
    {
        $asMainCard = Reading::query()
            ->where('card_id', $card->id)
            ->with('day')
            ->get()
            ->map(fn (Reading $reading): array => $this->appearance($reading->day, 'main', $reading->question));

        $asOtherCards = ReadingCard::query()
            ->where('card_id', $card->id)
            ->with('reading.day')
            ->get()
            ->map(fn (ReadingCard $entry): array => $this->appearance($entry->reading->day, $entry->kind->value, $entry->reading->question));

        return $asMainCard->concat($asOtherCards)
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function appearance(Day $day, string $role, ?string $question): array
    {
        return [
            'date' => $day->date->toDateString(),
            'dateLabel' => $day->date->translatedFormat('j M Y'),
            'role' => $role,
            'question' => $question,
            'eventNote' => $day->event_note,
            'url' => route('days.show', $day->date->toDateString()),
        ];
    }
}
