<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Services\CardCatalog;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    /**
     * Every event note, newest first, with its card, outcome and resonance.
     */
    public function index(CardCatalog $catalog): Response
    {
        $birthRoles = $catalog->birthRoles();

        $notes = Day::query()
            ->whereNotNull('event_note')
            ->with(['reading.card', 'themes'])
            ->orderByDesc('date')
            ->get()
            ->map(fn (Day $day): array => [
                'date' => $day->date->toDateString(),
                'dateLabel' => $day->date->translatedFormat('j M Y'),
                'url' => route('days.show', $day->date->toDateString()),
                'eventNote' => $day->event_note,
                'outcomeNote' => $day->outcome_note,
                'resonated' => $day->resonated,
                'themes' => $day->themes->pluck('name')->sort()->values(),
                'card' => $day->reading === null ? null : $catalog->present($day->reading->card, $birthRoles),
            ])
            ->values();

        return Inertia::render('Notes', [
            'notes' => $notes,
        ]);
    }
}
