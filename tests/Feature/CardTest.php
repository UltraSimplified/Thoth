<?php

use App\Models\Card;
use Database\Seeders\DeckSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(DeckSeeder::class));

it('writes major arcana numbers in Roman numerals', function (string $slug, ?string $numeral) {
    expect(Card::where('slug', $slug)->sole()->romanNumeral())->toBe($numeral);
})->with([
    ['the-fool', '0'],
    ['the-magus', 'I'],
    ['the-sun', 'XIX'],
    ['the-aeon', 'XX'],
    ['the-universe', 'XXI'],
    ['ace-of-wands', null],
]);

it('shows the card-back placeholder while a scan is missing', function () {
    Storage::fake('public');

    expect(Card::where('slug', 'the-aeon')->sole()->imageUrl())->toBe(asset('images/card-back.svg'));
});

it('links to the stored scan once it exists', function () {
    Storage::fake('public');
    Storage::disk('public')->put('cards/the-aeon.webp', 'image');

    expect(Card::where('slug', 'the-aeon')->sole()->imageUrl())
        ->toContain('/storage/cards/the-aeon.webp');
});

it('routes card pages by slug', function () {
    expect(Card::where('slug', 'the-aeon')->sole()->getRouteKey())->toBe('the-aeon');
});
