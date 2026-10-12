<?php

use App\Models\User;
use Database\Seeders\DeckSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DeckSeeder::class);
    $this->user = User::factory()->create();
});

it('outlines the six birth positions on the card page tree once a birth date is set', function () {
    $this->actingAs($this->user)
        ->patch(route('settings.update'), ['birth_date' => '1970-10-28'])
        ->assertRedirect();

    $birth = $this->actingAs($this->user)
        ->get(route('cards.show', 'the-moon'))
        ->viewData('page')['props']['tree']['birth'];

    $paths = $birth['paths'];
    sort($paths);

    $nodes = $birth['nodes'];
    sort($nodes);

    expect($paths)->toBe([12, 21, 24, 30])
        ->and($nodes)->toBe(['geburah', 'tiphareth']);
});

it('outlines no birth positions before a birth date is set', function () {
    $this->actingAs($this->user)
        ->get(route('cards.show', 'the-moon'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('CardShow')
            ->where('tree.birth.nodes', [])
            ->where('tree.birth.paths', [])
        );
});
