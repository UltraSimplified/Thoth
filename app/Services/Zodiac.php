<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Tropical zodiac arithmetic for the birth cards. Degrees run from 0 (0 Aries) to 360.
 */
final class Zodiac
{
    /**
     * The twelve signs in zodiac order, starting at 0 Aries.
     *
     * @var list<string>
     */
    public const SIGNS = [
        'Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo',
        'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces',
    ];

    /**
     * Court domains run from 21 degrees of their first sign to 20 degrees of the next.
     */
    public const COURT_SPAN_START_DEGREE = 21;

    public const COURT_SPAN_LENGTH = 30;

    public static function signOf(float $longitude): string
    {
        return self::SIGNS[(int) floor(self::normalise($longitude) / 30)];
    }

    /**
     * The decan (1, 2 or 3) of a longitude within its sign.
     */
    public static function decanOf(float $longitude): int
    {
        return (int) floor(fmod(self::normalise($longitude), 30) / 10) + 1;
    }

    /**
     * Whether a longitude falls inside the court domain that opens at 21 degrees of the given sign.
     */
    public static function courtSpanContains(string $fromSign, float $longitude): bool
    {
        $start = self::signIndex($fromSign) * 30 + self::COURT_SPAN_START_DEGREE;
        $offset = self::normalise($longitude - $start);

        return $offset < self::COURT_SPAN_LENGTH;
    }

    public static function signIndex(string $sign): int
    {
        return (int) array_search($sign, self::SIGNS, true);
    }

    /**
     * The Sun's ecliptic longitude at 12:00 UT on the given date, from the low-precision solar formula
     * (accurate to about 0.01 degrees). The date is treated as a whole day, so the time of birth is ignored.
     */
    public static function sunLongitudeOn(CarbonInterface $date): float
    {
        $noonUtc = CarbonImmutable::create($date->year, $date->month, $date->day, 12, 0, 0, 'UTC');
        $julianDay = $noonUtc->getTimestamp() / 86400 + 2440587.5;
        $daysSinceJ2000 = $julianDay - 2451545.0;

        $meanLongitude = 280.460 + 0.9856474 * $daysSinceJ2000;
        $meanAnomaly = deg2rad(357.528 + 0.9856003 * $daysSinceJ2000);

        return self::normalise(
            $meanLongitude + 1.915 * sin($meanAnomaly) + 0.020 * sin(2 * $meanAnomaly),
        );
    }

    /**
     * Wrap any angle into the range [0, 360).
     */
    private static function normalise(float $degrees): float
    {
        return fmod(fmod($degrees, 360) + 360, 360);
    }
}
