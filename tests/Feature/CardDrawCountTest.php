<?php

use App\Models\Card;
use App\Models\Day;
use App\Models\ReadingCard;
use App\Models\User;
use Database\Seeders\DeckSeeder;

beforeEach(function () {
    $this->seed(DeckSeeder::class);
    $this->user = User::factory()->create();
});

function drawnOn(string $date, string $slug): Day
{
    $day = Day::factory()->onDate($date)->create();
    $day->reading()->create(['card_id' => Card::where('slug', $slug)->sole()->id]);

    return $day;
}

it('counts main-card draws from 1 January 2026, not clarifiers or earlier years', function () {
    drawnOn('2025-12-31', 'the-aeon');   // before the cut-off
    drawnOn('2026-01-01', 'the-aeon');   // on the cut-off
    drawnOn('2026-03-14', 'the-aeon');
    drawnOn('2026-05-02', 'the-tower');

    $day = drawnOn('2026-06-01', 'the-moon');
    ReadingCard::create([
        'reading_id' => $day->reading->id,
        'card_id' => Card::where('slug', 'the-tower')->sole()->id,
        'kind' => 'clarifier',
        'position' => 1,
    ]);

    $response = $this->actingAs($this->user)->get(route('cards'));

    $cards = collect($response->viewData('page')['props']['cards'])->keyBy('slug');

    expect($cards['the-aeon']['drawsSince'])->toBe(2)
        ->and($cards['the-tower']['drawsSince'])->toBe(1)
        ->and($cards['the-moon']['drawsSince'])->toBe(1)
        ->and($cards['the-star']['drawsSince'])->toBe(0)
        ->and($response->viewData('page')['props']['drawsFrom'])->toBe('1 January 2026');
});

it('lists every card, including those never drawn, with a count', function () {
    $cards = $this->actingAs($this->user)
        ->get(route('cards'))
        ->viewData('page')['props']['cards'];

    expect($cards)->toHaveCount(78)
        ->and(collect($cards)->every(fn (array $card): bool => $card['drawsSince'] === 0))->toBeTrue();
});
