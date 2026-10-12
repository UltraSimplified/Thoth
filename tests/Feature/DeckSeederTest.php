<?php

use App\Models\Card;
use App\Sephira;
use App\Suit;
use Database\Seeders\DeckSeeder;

beforeEach(fn () => $this->seed(DeckSeeder::class));

it('seeds the 78 cards of the deck', function () {
    expect(Card::count())->toBe(78)
        ->and(Card::where('arcana', 'major')->count())->toBe(22)
        ->and(Card::where('arcana', 'minor')->count())->toBe(56);
});

it('seeds 14 cards for each suit', function () {
    foreach (Suit::cases() as $suit) {
        expect(Card::where('suit', $suit)->count())->toBe(14);
    }
});

it('seeds the pip attributions from the spec', function () {
    $fiveOfCups = Card::where('slug', 'five-of-cups')->sole();

    expect($fiveOfCups->title)->toBe('Disappointment')
        ->and($fiveOfCups->planet)->toBe('Mars')
        ->and($fiveOfCups->sign)->toBe('Scorpio')
        ->and($fiveOfCups->decan)->toBe(1)
        ->and($fiveOfCups->element)->toBe('water')
        ->and($fiveOfCups->sephira)->toBe(Sephira::Geburah);

    $tenOfDisks = Card::where('slug', 'ten-of-disks')->sole();

    expect($tenOfDisks->planet)->toBe('Mercury')
        ->and($tenOfDisks->sign)->toBe('Virgo')
        ->and($tenOfDisks->decan)->toBe(3);
});

it('seeds court sub-elements and zodiac spans from the spec', function () {
    $knightOfCups = Card::where('slug', 'knight-of-cups')->sole();

    expect($knightOfCups->element)->toBe('water')
        ->and($knightOfCups->sub_element)->toBe('fire')
        ->and($knightOfCups->sephira)->toBe(Sephira::Chokmah);

    $princeOfCups = Card::where('slug', 'prince-of-cups')->sole();

    expect($princeOfCups->zodiac_span_from)->toBe('Libra')
        ->and($princeOfCups->zodiac_span_to)->toBe('Scorpio')
        ->and($princeOfCups->sephira)->toBe(Sephira::Tiphareth);
});

it('seeds the quadrants of the aces and princesses', function () {
    expect(Card::where('slug', 'ace-of-wands')->sole()->quadrant)
        ->toBe('Cancer-Leo-Virgo, above the North Pole (Asia)')
        ->and(Card::where('slug', 'princess-of-disks')->sole()->quadrant)
        ->toBe('Aries-Taurus-Gemini, above the North Pole (Europe and Africa)');
});

it('seeds the Tree path of each major arcana card', function () {
    expect(Card::where('slug', 'death')->sole()->tree_path)->toBe(24)
        ->and(Card::where('slug', 'the-emperor')->sole()->tree_path)->toBe(28)
        ->and(Card::where('slug', 'the-star')->sole()->tree_path)->toBe(15)
        ->and(Card::where('slug', 'the-magus')->sole()->tree_path)->toBe(12);
});

it('gives every card its own thothreadings page', function () {
    $urls = Card::pluck('thothreadings_url');

    expect($urls->unique())->toHaveCount(78)
        ->and($urls->every(fn (string $url): bool => str_starts_with($url, 'https://thothreadings.com/blog/')))->toBeTrue();
});

it('keeps personal notes when run again', function () {
    Card::where('slug', 'the-aeon')->sole()->update(['personal_note' => 'Renewal, not an ending.']);

    $this->seed(DeckSeeder::class);

    expect(Card::count())->toBe(78)
        ->and(Card::where('slug', 'the-aeon')->sole()->personal_note)->toBe('Renewal, not an ending.');
});
