<?php

use App\Models\Card;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DeckSeeder;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DeckSeeder::class);
    Date::setTestNow(CarbonImmutable::parse('2026-10-12 09:00', 'Europe/London'));
    $this->user = User::factory()->create();
});

afterEach(fn () => Date::setTestNow());

it('names the day\'s cards first, then the six birth positions', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'card_id' => Card::where('slug', 'four-of-cups')->sole()->id,
            'jumper_ids' => [Card::where('slug', 'the-tower')->sole()->id],
            'clarifier_ids' => [],
        ]);

    $this->actingAs($this->user)
        ->patch(route('settings.update'), ['birth_date' => '1970-10-28']);

    $elements = $this->actingAs($this->user)
        ->get(route('days.show', '2026-10-12'))
        ->viewData('page')['props']['tree']['elements'];

    expect(collect($elements)->pluck('role')->all())
        ->toBe(['main', 'jumper', 'birth', 'birth', 'birth', 'birth', 'birth', 'birth'])
        ->and($elements[0])->toMatchArray([
            'label' => 'Main card',
            'card' => 'Four of Cups',
            'sephira' => 'chesed',
            'path' => null,
        ])
        ->and($elements[1])->toMatchArray([
            'label' => 'Jumper',
            'card' => 'The Tower',
            'sephira' => null,
            'path' => 27,
        ])
        ->and($elements[2])->toMatchArray([
            'label' => 'Birth: Atu',
            'card' => 'The Magus',
        ])
        ->and($elements[5])->toMatchArray([
            'label' => 'Birth: Decan card',
            'card' => 'Five of Cups',
            'sephira' => 'geburah',
            'path' => null,
        ]);
});

it('names no birth positions before a birth date is set', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'card_id' => Card::where('slug', 'four-of-cups')->sole()->id,
        ]);

    $this->actingAs($this->user)
        ->get(route('days.show', '2026-10-12'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DayShow')
            ->has('tree.elements', 1)
            ->where('tree.elements.0.role', 'main')
        );
});

it('names the card on its own page first, then the birth positions', function () {
    $this->actingAs($this->user)
        ->patch(route('settings.update'), ['birth_date' => '1970-10-28']);

    $elements = $this->actingAs($this->user)
        ->get(route('cards.show', 'the-moon'))
        ->viewData('page')['props']['tree']['elements'];

    expect($elements)->toHaveCount(7)
        ->and($elements[0])->toMatchArray([
            'role' => 'main',
            'label' => 'This card',
            'card' => 'The Moon',
            'path' => 29,
            'sephira' => null,
        ]);
});
