<?php

use App\Models\Card;
use App\Sephira;
use App\Services\TreePlacement;
use App\TreePlacementStyle;
use Database\Seeders\DeckSeeder;

beforeEach(fn () => $this->seed(DeckSeeder::class));

it('describes all 22 paths of the Tree', function () {
    expect(TreePlacement::PATHS)->toHaveCount(22)
        ->and(array_keys(TreePlacement::PATHS))->toBe(range(11, 32))
        ->and(TreePlacement::PATHS[13])->toBe(['letter' => 'Gimel', 'from' => Sephira::Kether, 'to' => Sephira::Tiphareth]);
});

it('places the Emperor and the Star as Crowley does by default', function () {
    $placement = new TreePlacement;

    expect($placement->positionFor(Card::where('slug', 'the-emperor')->sole())['path'])->toBe(28)
        ->and($placement->positionFor(Card::where('slug', 'the-star')->sole())['path'])->toBe(15);
});

it('swaps the Emperor and the Star for the Golden Dawn placement', function () {
    $placement = new TreePlacement(TreePlacementStyle::GoldenDawn);

    expect($placement->positionFor(Card::where('slug', 'the-emperor')->sole())['path'])->toBe(15)
        ->and($placement->positionFor(Card::where('slug', 'the-star')->sole())['path'])->toBe(28)
        ->and($placement->positionFor(Card::where('slug', 'the-magus')->sole())['path'])->toBe(12);
});

it('places pips and courts on their sephiroth', function () {
    $placement = new TreePlacement;

    expect($placement->positionFor(Card::where('slug', 'three-of-cups')->sole()))
        ->toBe(['path' => null, 'sephira' => Sephira::Binah])
        ->and($placement->positionFor(Card::where('slug', 'queen-of-disks')->sole())['sephira'])->toBe(Sephira::Binah);
});

it('shows Binah holding the four Threes and the four Queens', function () {
    $cards = Card::query()->get();
    $binah = (new TreePlacement)->cardsByPosition($cards)['nodes']['binah'];

    expect($binah)->toHaveCount(8)
        ->and(collect($binah)->pluck('slug')->sort()->values()->all())->toContain('three-of-cups', 'queen-of-swords');
});

it('lights the paths and sephiroth of a set of cards once each', function () {
    $cards = Card::whereIn('slug', ['the-aeon', 'ace-of-wands', 'queen-of-disks', 'the-moon'])->get();

    expect((new TreePlacement)->highlightsFor($cards))->toBe([
        'nodes' => ['kether', 'binah'],
        'paths' => [31, 29],
    ]);
});
