<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\SeasonDefinition;
use App\Services\AppSettings;
use App\Services\CardCatalog;
use App\ThemePreference;
use App\TreePlacementStyle;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * The settings page: theme, birth date and the birth cards it produces, season, Tree placement and excerpts.
     */
    public function show(AppSettings $settings, CardCatalog $catalog): Response
    {
        return Inertia::render('Settings', [
            'status' => session('status'),
            'settings' => [
                'theme' => $settings->theme()->value,
                'birthDate' => $settings->birthDate()?->toDateString(),
                'seasonDefinition' => $settings->seasonDefinition()->value,
                'treePlacement' => $settings->treePlacement()->value,
                'showExcerpts' => $settings->showExcerpts(),
            ],
            'birthCards' => collect($catalog->birthEntries())
                ->map(fn (array $entry): array => [
                    'role' => $entry['role']->label(),
                    'name' => $entry['card']->name,
                    'slug' => $entry['card']->slug,
                ])
                ->values(),
        ]);
    }

    /**
     * Save any of the settings sent. The theme switcher sends the theme alone.
     */
    public function update(UpdateSettingsRequest $request, AppSettings $settings): RedirectResponse
    {
        $validated = $request->validated();

        if (array_key_exists('theme', $validated)) {
            $settings->setTheme(ThemePreference::from($validated['theme']));
        }

        if (array_key_exists('birth_date', $validated)) {
            $settings->setBirthDate(
                $validated['birth_date'] === null ? null : CarbonImmutable::parse($validated['birth_date'])->startOfDay(),
            );
        }

        if (array_key_exists('season_definition', $validated)) {
            $settings->setSeasonDefinition(SeasonDefinition::from($validated['season_definition']));
        }

        if (array_key_exists('tree_placement', $validated)) {
            $settings->setTreePlacement(TreePlacementStyle::from($validated['tree_placement']));
        }

        if (array_key_exists('show_excerpts', $validated)) {
            $settings->setShowExcerpts((bool) $validated['show_excerpts']);
        }

        if (array_key_exists('journal_timezone_confirmed', $validated) && $validated['journal_timezone_confirmed']) {
            $settings->confirmJournalTimezone();
        }

        return back();
    }
}
