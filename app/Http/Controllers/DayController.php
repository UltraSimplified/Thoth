<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDayRequest;
use App\Http\Requests\SaveEveningRequest;
use App\Models\Day;
use App\Services\DayPage;
use App\Services\DayWriter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DayController extends Controller
{
    /**
     * The day page for any date (YYYY-MM-DD).
     */
    public function show(string $date, DayPage $dayPage): Response
    {
        return Inertia::render('DayShow', $dayPage->props($this->dateFrom($date)));
    }

    /**
     * The edit page for a day. Locked fields are shown read-only.
     */
    public function edit(string $date, DayPage $dayPage): Response
    {
        return Inertia::render('DayEdit', $dayPage->props($this->dateFrom($date)));
    }

    /**
     * Save the morning fields. The date comes from the form's URL, so a form opened before midnight keeps its date.
     */
    public function update(SaveDayRequest $request, string $date, DayWriter $writer): RedirectResponse
    {
        $day = $this->dateFrom($date);

        Gate::authorize('updateMorning', $this->dayFor($day));

        $writer->saveMorning($day, $request->morningData());

        return back();
    }

    /**
     * Save the outcome, resonance and themes. These have no lock, so any day's evening can be edited.
     */
    public function updateEvening(SaveEveningRequest $request, string $date, DayWriter $writer): RedirectResponse
    {
        $day = $this->dateFrom($date);

        $writer->saveEvening($day, $request->eveningData());

        return back();
    }

    private function dateFrom(string $date): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('Y-m-d', $date);

        if (! $parsed instanceof CarbonImmutable || $parsed->format('Y-m-d') !== $date) {
            abort(404);
        }

        return $parsed->startOfDay();
    }

    /**
     * The day as it stands, or an unsaved day for a date with no record yet, so the lock rules still apply.
     */
    private function dayFor(CarbonImmutable $date): Day
    {
        return Day::query()->firstOrNew(['date' => $date->toDateString()]);
    }
}
