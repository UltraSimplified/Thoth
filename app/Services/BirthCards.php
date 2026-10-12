<?php

namespace App\Services;

use App\BirthCardRole;
use App\Models\Card;
use Carbon\CarbonImmutable;

/**
 * Works out the six birth cards from a date of birth: three birth Atus from the date's digits, the decan
 * card and the Sun-sign Atu from the Sun's sign, and the court whose domain holds the Sun.
 */
final class BirthCards
{
    /**
     * @return list<array{role: BirthCardRole, card: Card}>
     */
    public function forBirthDate(CarbonImmutable $birthDate): array
    {
        $sunLongitude = Zodiac::sunLongitudeOn($birthDate);
        $sign = Zodiac::signOf($sunLongitude);
        $decan = Zodiac::decanOf($sunLongitude);

        $birthCards = [];

        foreach ($this->birthAtuNumbers($birthDate) as $number) {
            $birthCards[] = [
                'role' => BirthCardRole::BirthAtu,
                'card' => Card::query()->where('arcana', 'major')->where('number', $number)->sole(),
            ];
        }

        $birthCards[] = [
            'role' => BirthCardRole::DecanCard,
            'card' => Card::query()->where('arcana', 'minor')->where('sign', $sign)->where('decan', $decan)->sole(),
        ];

        $birthCards[] = [
            'role' => BirthCardRole::SunSignAtu,
            'card' => Card::query()->where('arcana', 'major')->where('sign', $sign)->sole(),
        ];

        $court = Card::query()
            ->whereIn('rank', ['knight', 'prince', 'queen'])
            ->get()
            ->first(fn (Card $card): bool => Zodiac::courtSpanContains($card->zodiac_span_from, $sunLongitude));

        $birthCards[] = [
            'role' => BirthCardRole::CourtCard,
            'card' => $court ?? throw new \RuntimeException('No court card rules the Sun at '.$birthDate->toDateString()),
        ];

        return $birthCards;
    }

    /**
     * The three Atu numbers of the birth triad. The digits of day, month and year are summed down to a single
     * digit n, and the triad is n, n + 9 and n + 18, wrapping past The Universe (XXI) back to The Fool (0).
     * For 28 October 1970: 2008 becomes 10, then 1, so the triad is I, X and XIX.
     *
     * @return list<int>
     */
    public function birthAtuNumbers(CarbonImmutable $birthDate): array
    {
        $total = (int) $birthDate->format('j') + (int) $birthDate->format('n') + (int) $birthDate->format('Y');

        while ($total > 9) {
            $total = array_sum(str_split((string) $total));
        }

        return array_map(
            fn (int $offset): int => ($total + $offset) % 22,
            [0, 9, 18],
        );
    }
}
