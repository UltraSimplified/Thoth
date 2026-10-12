<?php

namespace App\Services;

use App\Models\Setting;
use App\SeasonDefinition;
use App\ThemePreference;
use App\TreePlacementStyle;
use Carbon\CarbonImmutable;

/**
 * Typed access to the single user's preferences, stored as key/value rows in the settings table.
 */
final class AppSettings
{
    public const THEME = 'theme';

    public const BIRTH_DATE = 'birth_date';

    public const SEASON_DEFINITION = 'season_definition';

    public const TREE_PLACEMENT = 'tree_placement';

    public const SHOW_EXCERPTS = 'show_excerpts';

    public const JOURNAL_TIMEZONE_CONFIRMED = 'journal_timezone_confirmed';

    public function theme(): ThemePreference
    {
        return ThemePreference::tryFrom(Setting::read(self::THEME, '') ?? '') ?? ThemePreference::System;
    }

    public function birthDate(): ?CarbonImmutable
    {
        $birthDate = Setting::read(self::BIRTH_DATE);

        return $birthDate === null ? null : CarbonImmutable::parse($birthDate)->startOfDay();
    }

    public function seasonDefinition(): SeasonDefinition
    {
        return SeasonDefinition::tryFrom(Setting::read(self::SEASON_DEFINITION, '') ?? '')
            ?? SeasonDefinition::Meteorological;
    }

    public function treePlacement(): TreePlacementStyle
    {
        return TreePlacementStyle::tryFrom(Setting::read(self::TREE_PLACEMENT, '') ?? '')
            ?? TreePlacementStyle::Crowley;
    }

    public function showExcerpts(): bool
    {
        return Setting::read(self::SHOW_EXCERPTS, '0') === '1';
    }

    public function journalTimezoneConfirmed(): bool
    {
        return Setting::read(self::JOURNAL_TIMEZONE_CONFIRMED, '0') === '1';
    }

    public function confirmJournalTimezone(): void
    {
        Setting::write(self::JOURNAL_TIMEZONE_CONFIRMED, '1');
    }

    public function setTheme(ThemePreference $theme): void
    {
        Setting::write(self::THEME, $theme->value);
    }

    public function setBirthDate(?CarbonImmutable $birthDate): void
    {
        Setting::write(self::BIRTH_DATE, $birthDate?->toDateString());
    }

    public function setSeasonDefinition(SeasonDefinition $seasonDefinition): void
    {
        Setting::write(self::SEASON_DEFINITION, $seasonDefinition->value);
    }

    public function setTreePlacement(TreePlacementStyle $treePlacement): void
    {
        Setting::write(self::TREE_PLACEMENT, $treePlacement->value);
    }

    public function setShowExcerpts(bool $showExcerpts): void
    {
        Setting::write(self::SHOW_EXCERPTS, $showExcerpts ? '1' : '0');
    }
}
