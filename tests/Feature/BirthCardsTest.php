<?php

use App\BirthCardRole;
use App\Models\Card;
use App\Services\BirthCards;
use App\Services\TreePlacement;
use App\Services\Zodiac;
use App\TreePlacementStyle;
use Carbon\CarbonImmutable;
use Database\Seeders\DeckSeeder;

beforeEach(fn () => $this->seed(DeckSeeder::class));

it('places the Sun for 28 October 1970 in the first decan of Scorpio', function () {
    $longitude = Zodiac::sunLongitudeOn(CarbonImmutable::create(1970, 10, 28));

    expect($longitude)->toBeGreaterThan(214)
        ->toBeLessThan(215.5)
        ->and(Zodiac::signOf($longitude))->toBe('Scorpio')
        ->and(Zodiac::decanOf($longitude))->toBe(1)
        ->and(Zodiac::courtSpanContains('Libra', $longitude))->toBeTrue();
});

it('reduces the birth date to the Atu triad from the spec', function () {
    expect((new BirthCards)->birthAtuNumbers(CarbonImmutable::create(1970, 10, 28)))->toBe([1, 10, 19]);
});

it('wraps the birth triad past The Universe', function () {
    // 2 + 1 + 1990 = 1993, which reduces to 4: the triad is 4, 13 and 22, and 22 wraps to The Fool (0).
    expect((new BirthCards)->birthAtuNumbers(CarbonImmutable::create(1990, 1, 2)))->toBe([4, 13, 0]);

    // 3 + 1 + 2001 = 2005, which reduces to 7: the triad is 7, 16 and 25, and 25 wraps to The Empress (3).
    expect((new BirthCards)->birthAtuNumbers(CarbonImmutable::create(2001, 1, 3)))->toBe([7, 16, 3]);
});

it('finds the six birth cards for 28 October 1970', function () {
    $birthCards = collect((new BirthCards)->forBirthDate(CarbonImmutable::create(1970, 10, 28)));

    expect($birthCards->map(fn (array $entry): string => $entry['card']->name)->all())->toBe([
        'The Magus',
        'Fortune',
        'The Sun',
        'Five of Cups',
        'Death',
        'Prince of Cups',
    ])
        ->and($birthCards->pluck('role')->all())->toBe([
            BirthCardRole::BirthAtu,
            BirthCardRole::BirthAtu,
            BirthCardRole::BirthAtu,
            BirthCardRole::DecanCard,
            BirthCardRole::SunSignAtu,
            BirthCardRole::CourtCard,
        ]);
});

it('places the six birth cards on their six Tree positions', function () {
    $cards = collect((new BirthCards)->forBirthDate(CarbonImmutable::create(1970, 10, 28)))
        ->pluck('card');

    expect((new TreePlacement)->highlightsFor($cards))->toBe([
        'nodes' => ['geburah', 'tiphareth'],
        'paths' => [12, 21, 30, 24],
    ]);
});

it('keeps the birth positions on the Golden Dawn placement too', function () {
    $cards = collect((new BirthCards)->forBirthDate(CarbonImmutable::create(1970, 10, 28)))
        ->pluck('card');

    $paths = (new TreePlacement(TreePlacementStyle::GoldenDawn))->highlightsFor($cards)['paths'];

    expect($paths)->toBe([12, 21, 30, 24]);
});

it('labels each birth card role', function () {
    expect(BirthCardRole::SunSignAtu->label())->toBe('Sun-sign Atu')
        ->and(BirthCardRole::CourtCard->label())->toBe('Court card')
        ->and(Card::where('slug', 'death')->sole()->sign)->toBe('Scorpio');
});
