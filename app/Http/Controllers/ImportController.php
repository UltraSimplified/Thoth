<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewImportRequest;
use App\Http\Requests\StoreImportRequest;
use App\Models\Day;
use App\Services\CardCatalog;
use App\Services\DayWriter;
use App\Services\ReadingImporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    /**
     * The paste-in importer.
     */
    public function show(): Response
    {
        return Inertia::render('Import', [
            'review' => null,
            'cards' => [],
            'defaultYear' => (int) today()->year,
            'status' => session('status'),
        ]);
    }

    /**
     * Parse the pasted list and show every day with its problems before anything is saved.
     */
    public function review(ReviewImportRequest $request, ReadingImporter $importer, CardCatalog $catalog): Response
    {
        $text = (string) $request->validated('text');
        $year = (int) $request->validated('year');

        $rows = $this->withExistingDays($importer->parse($text, $year));

        return Inertia::render('Import', [
            'review' => [
                'text' => $text,
                'year' => $year,
                'rows' => $rows,
            ],
            'cards' => $catalog->all(),
            'defaultYear' => $year,
            'status' => null,
        ]);
    }

    /**
     * Save the rows the owner confirmed. Days already recorded are replaced only when their row was confirmed.
     */
    public function store(StoreImportRequest $request, DayWriter $writer): RedirectResponse
    {
        $days = $request->confirmedDays();

        DB::transaction(function () use ($days, $writer): void {
            foreach ($days as $day) {
                $writer->saveMorning(CarbonImmutable::parse($day['date'])->startOfDay(), $day);
            }
        });

        $count = count($days);

        return redirect()
            ->route('imports')
            ->with('status', 'Imported '.$count.' '.Str::plural('day', $count).'.');
    }

    /**
     * Rows whose date already holds a reading are not confirmed by default, and say so.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function withExistingDays(array $rows): array
    {
        $dates = array_values(array_filter(array_column($rows, 'date')));

        $recorded = Day::query()
            ->whereIn('date', $dates)
            ->with('reading')
            ->get()
            ->filter(fn (Day $day): bool => $day->reading !== null || $day->event_note !== null)
            ->map(fn (Day $day): string => $day->date->toDateString())
            ->all();

        return array_map(function (array $row) use ($recorded): array {
            $alreadyRecorded = $row['date'] !== null && in_array($row['date'], $recorded, true);

            if ($alreadyRecorded) {
                $row['problems'][] = 'This day already has a reading. Confirming replaces it.';
            }

            return $row + ['include' => $row['problems'] === []];
        }, $rows);
    }
}
