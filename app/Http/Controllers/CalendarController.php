<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Day;
use App\Models\Theme;
use App\Suit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    private const LIST_PAGE_SIZE = 40;

    private const ELEMENTS = ['fire', 'water', 'air', 'earth'];

    /**
     * The calendar at its current month.
     */
    public function current(Request $request): RedirectResponse
    {
        $today = today();

        return redirect()->route('calendar.month', [
            'year' => $today->year,
            'month' => $today->month,
            ...$request->query(),
        ]);
    }

    /**
     * The month grid, or the filtered list of days when the list view is chosen.
     */
    public function show(Request $request, int $year, int $month): Response
    {
        $month = CarbonImmutable::create($year, $month, 1)->startOfDay();

        if ($request->query('view') === 'list') {
            return Inertia::render('Calendar', $this->listProps($request, $month));
        }

        return Inertia::render('Calendar', [
            'view' => 'grid',
            'month' => $this->monthNavigation($month),
            'weekdays' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'weeks' => $this->weeks($month),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function monthNavigation(CarbonImmutable $month): array
    {
        $previous = $month->subMonthNoOverflow();
        $next = $month->addMonthNoOverflow();

        return [
            'label' => $month->translatedFormat('F Y'),
            'prevUrl' => route('calendar.month', ['year' => $previous->year, 'month' => $previous->month]),
            'nextUrl' => route('calendar.month', ['year' => $next->year, 'month' => $next->month]),
            'todayUrl' => route('calendar'),
            'gridUrl' => route('calendar.month', ['year' => $month->year, 'month' => $month->month, 'view' => 'grid']),
            'listUrl' => route('calendar.month', ['year' => $month->year, 'month' => $month->month, 'view' => 'list']),
        ];
    }

    /**
     * Monday-first weeks covering the month. Each cell links to the day page, or to the form for an empty day.
     *
     * @return list<list<array<string, mixed>>>
     */
    private function weeks(CarbonImmutable $month): array
    {
        $start = $month->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
        $end = $month->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);

        $days = Day::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with(['reading.card', 'reading.jumpers', 'reading.clarifiers'])
            ->get()
            ->keyBy(fn (Day $day): string => $day->date->toDateString());

        $today = today()->toDateString();
        $weeks = [];
        $week = [];

        for ($cursor = $start; $cursor->lte($end); $cursor = $cursor->addDay()) {
            $key = $cursor->toDateString();
            $day = $days->get($key);
            $reading = $day?->reading;

            $week[] = [
                'date' => $key,
                'dayNumber' => $cursor->day,
                'inMonth' => $cursor->month === $month->month,
                'isToday' => $key === $today,
                'url' => $day === null ? route('days.edit', $key) : route('days.show', $key),
                'noteOnly' => $day !== null && $reading === null,
                'card' => $reading === null ? null : [
                    'name' => $reading->card->name,
                    'thumbnailUrl' => $reading->card->thumbnailUrl(),
                    'suit' => $reading->card->suit?->value,
                ],
                'jumpers' => $reading?->jumpers->count() ?? 0,
                'clarifiers' => $reading?->clarifiers->count() ?? 0,
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return $weeks;
    }

    /**
     * @return array<string, mixed>
     */
    private function listProps(Request $request, CarbonImmutable $month): array
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'suit' => ['nullable', Rule::enum(Suit::class)],
            'arcana' => ['nullable', Rule::in(['major', 'minor'])],
            'element' => ['nullable', Rule::in(self::ELEMENTS)],
            'planet' => ['nullable', 'string', 'max:20'],
            'sign' => ['nullable', 'string', 'max:20'],
            'theme' => ['nullable', 'string', 'max:60'],
            'resonance' => ['nullable', Rule::in(['yes', 'no'])],
        ]);

        $page = $this->filteredDays($filters)
            ->simplePaginate(self::LIST_PAGE_SIZE)
            ->withQueryString();

        return [
            'view' => 'list',
            'month' => $this->monthNavigation($month),
            'filters' => $filters,
            'options' => [
                'suits' => collect(Suit::cases())->map(fn (Suit $suit): array => ['value' => $suit->value, 'label' => $suit->label()])->values(),
                'elements' => self::ELEMENTS,
                'planets' => Card::query()->whereNotNull('planet')->distinct()->orderBy('planet')->pluck('planet')->values(),
                'signs' => Card::query()->whereNotNull('sign')->distinct()->orderBy('sign')->pluck('sign')->values(),
                'themes' => Theme::query()->orderBy('name')->pluck('name')->values(),
            ],
            'days' => [
                'data' => $page->getCollection()->map(fn (Day $day): array => $this->listRow($day))->values()->all(),
                'prev_page_url' => $page->previousPageUrl(),
                'next_page_url' => $page->nextPageUrl(),
            ],
        ];
    }

    /**
     * Apply the list filters. Card filters look at the main card only, because jumpers never enter the main statistics.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Day>
     */
    private function filteredDays(array $filters): Builder
    {
        $query = Day::query()
            ->with(['reading.card', 'themes'])
            ->orderByDesc('date');

        if (filled($filters['from'] ?? null)) {
            $query->where('date', '>=', $filters['from']);
        }

        if (filled($filters['to'] ?? null)) {
            $query->where('date', '<=', $filters['to']);
        }

        if (filled($filters['q'] ?? null)) {
            $term = '%'.$filters['q'].'%';

            $query->where(function ($search) use ($term): void {
                $search->where('event_note', 'like', $term)
                    ->orWhere('outcome_note', 'like', $term)
                    ->orWhereHas('reading', fn ($reading) => $reading
                        ->where('question', 'like', $term)
                        ->orWhere('interpretation_notes', 'like', $term));
            });
        }

        if (($filters['resonance'] ?? null) !== null) {
            $query->where('resonated', $filters['resonance'] === 'yes');
        }

        if (filled($filters['theme'] ?? null)) {
            $query->whereHas('themes', fn ($themes) => $themes->whereRaw('LOWER(name) = ?', [mb_strtolower($filters['theme'])]));
        }

        $cardFilters = array_filter(
            [
                'suit' => $filters['suit'] ?? null,
                'arcana' => $filters['arcana'] ?? null,
                'element' => $filters['element'] ?? null,
                'planet' => $filters['planet'] ?? null,
                'sign' => $filters['sign'] ?? null,
            ],
            fn (mixed $value): bool => filled($value),
        );

        if ($cardFilters !== []) {
            $query->whereHas('reading.card', function ($card) use ($cardFilters): void {
                foreach ($cardFilters as $column => $value) {
                    $card->where($column, $value);
                }
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(Day $day): array
    {
        $reading = $day->reading;

        return [
            'date' => $day->date->toDateString(),
            'dateLabel' => $day->date->translatedFormat('l j F Y'),
            'url' => route('days.show', $day->date->toDateString()),
            'card' => $reading === null ? null : [
                'name' => $reading->card->name,
                'thumbnailUrl' => $reading->card->thumbnailUrl(),
            ],
            'question' => $reading?->question,
            'eventNote' => $day->event_note,
            'resonated' => $day->resonated,
            'themes' => $day->themes->pluck('name')->sort()->values(),
        ];
    }
}
