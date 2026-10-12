<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Day;
use App\Models\Reading;
use App\Models\ReadingCard;
use App\Services\AppSettings;
use App\Services\CardCatalog;
use App\Services\CardImageStore;
use App\Services\TreePlacement;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    /**
     * The date from which the index counts how often each card has been drawn.
     */
    private const DRAWS_COUNTED_FROM = '2026-01-01';

    /**
     * The card index, grouped by suit on the page. Each card carries how often it was drawn as the main card since the cut-off.
     */
    public function index(CardCatalog $catalog): Response
    {
        $draws = $this->mainCardDrawsFrom(self::DRAWS_COUNTED_FROM);

        return Inertia::render('Cards', [
            'cards' => collect($catalog->all())
                ->map(fn (array $card): array => $card + ['drawsSince' => $draws[$card['id']] ?? 0])
                ->values()
                ->all(),
            'drawsFrom' => CarbonImmutable::parse(self::DRAWS_COUNTED_FROM)->translatedFormat('j F Y'),
        ]);
    }

    /**
     * How many days each card was drawn as the main card on or after the given date, keyed by card id.
     * Clarifiers and jumpers are not counted.
     *
     * @return array<int, int>
     */
    private function mainCardDrawsFrom(string $date): array
    {
        return Reading::query()
            ->join('days', 'days.id', '=', 'readings.day_id')
            ->where('days.date', '>=', $date)
            ->groupBy('readings.card_id')
            ->selectRaw('readings.card_id, count(*) as draws')
            ->pluck('draws', 'card_id')
            ->map(fn (mixed $draws): int => (int) $draws)
            ->all();
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
     * Replace a card's image with an uploaded scan. The scan is cropped and resized the same way as an import,
     * so the new image shows on every day the card appears.
     */
    public function updateImage(Request $request, Card $card, CardImageStore $images): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
        ]);

        /** @var UploadedFile $upload */
        $upload = $request->file('image');

        if (! $images->storeScan($card, (string) file_get_contents($upload->getRealPath()))) {
            throw ValidationException::withMessages([
                'image' => 'We could not read that image. Try a JPEG, PNG or WebP scan.',
            ]);
        }

        return back();
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
