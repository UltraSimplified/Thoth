<?php

use App\Models\Card;
use App\Models\Day;
use App\Models\Reading;
use App\Models\ReadingCard;
use App\Models\Theme;
use App\Models\User;
use App\ReadingCardKind;
use Carbon\CarbonImmutable;
use Database\Seeders\DeckSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DeckSeeder::class);
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-12 09:00', 'Europe/London'));
    $this->user = User::factory()->create();
});

afterEach(fn () => Carbon::setTestNow());

function card(string $slug): Card
{
    return Card::where('slug', $slug)->sole();
}

it('shows the morning form on Today before a card is logged', function () {
    $this->actingAs($this->user)
        ->get(route('today'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Today')
            ->where('date', '2026-10-12')
            ->where('isToday', true)
            ->where('day', null)
            ->has('cards', 78)
            ->where('locks.morning', false)
            ->where('locks.evening', false)
        );
});

it('saves a main card with its jumpers and clarifiers in the order given', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'event_note' => 'The post arrived late.',
            'card_id' => card('the-aeon')->id,
            'question' => 'What is ending?',
            'interpretation_notes' => 'Air unchallenged',
            'jumper_ids' => [card('ace-of-cups')->id, card('the-moon')->id],
            'clarifier_ids' => [card('seven-of-disks')->id],
        ])
        ->assertRedirect();

    $day = Day::where('date', '2026-10-12')->sole();
    $reading = $day->reading;

    expect($day->event_note)->toBe('The post arrived late.')
        ->and($reading->card->slug)->toBe('the-aeon')
        ->and($reading->question)->toBe('What is ending?')
        ->and($reading->jumpers->map(fn (ReadingCard $entry) => $entry->card->slug)->all())->toBe(['ace-of-cups', 'the-moon'])
        ->and($reading->jumpers->pluck('position')->all())->toBe([1, 2])
        ->and($reading->clarifiers->map(fn (ReadingCard $entry) => $entry->card->slug)->all())->toBe(['seven-of-disks'])
        ->and($reading->clarifiers->first()->kind)->toBe(ReadingCardKind::Clarifier);
});

it('keeps a note-only day when no card is drawn', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'event_note' => 'Met an old friend.',
            'card_id' => null,
            'jumper_ids' => [],
            'clarifier_ids' => [],
        ])
        ->assertRedirect();

    $day = Day::where('date', '2026-10-12')->sole();

    expect($day->event_note)->toBe('Met an old friend.')
        ->and(Reading::count())->toBe(0);
});

it('removes an empty day rather than keeping a blank record', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'event_note' => '',
            'card_id' => null,
        ]);

    expect(Day::count())->toBe(0);
});

it('rejects a question or clarifier without a main card', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'question' => 'Should I wait?',
            'card_id' => null,
        ])
        ->assertSessionHasErrors('card_id');

    expect(Day::count())->toBe(0);
});

it('rejects the same card twice in one day', function () {
    $aeon = card('the-aeon')->id;

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'card_id' => $aeon,
            'jumper_ids' => [$aeon],
        ])
        ->assertSessionHasErrors('card_id');
});

it('limits the event note to 255 characters', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'event_note' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('event_note');
});

it('saves to the date in the form URL even after midnight', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-13 00:02', 'Europe/London'));

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), ['event_note' => 'Written after midnight.'])
        ->assertRedirect();

    expect(Day::where('date', '2026-10-12')->sole()->event_note)->toBe('Written after midnight.')
        ->and(Day::where('date', '2026-10-13')->exists())->toBeFalse();
});

it('keeps the morning fields editable through the seventh day after the date', function () {
    Day::factory()->onDate('2026-10-05')->create(['event_note' => 'Original.']);

    Carbon::setTestNow(CarbonImmutable::parse('2026-10-12 09:00', 'Europe/London'));

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-05'), ['event_note' => 'Changed on the seventh day.'])
        ->assertRedirect();

    expect(Day::where('date', '2026-10-05')->sole()->event_note)->toBe('Changed on the seventh day.');
});

it('locks the morning fields at midnight at the end of the seventh day', function () {
    Day::factory()->onDate('2026-10-04')->create(['event_note' => 'Original.']);

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-04'), ['event_note' => 'Too late.'])
        ->assertForbidden();

    expect(Day::where('date', '2026-10-04')->sole()->event_note)->toBe('Original.');
});

it('closes the evening section at the end of the following day', function () {
    Day::factory()->onDate('2026-10-11')->create();

    $this->actingAs($this->user)
        ->put(route('days.evening.update', '2026-10-11'), ['outcome_note' => 'Allowed on the next day.', 'themes' => []])
        ->assertRedirect();

    Carbon::setTestNow(CarbonImmutable::parse('2026-10-13 09:00', 'Europe/London'));

    $this->actingAs($this->user)
        ->put(route('days.evening.update', '2026-10-11'), ['outcome_note' => 'Too late.', 'themes' => []])
        ->assertForbidden();

    expect(Day::where('date', '2026-10-11')->sole()->outcome_note)->toBe('Allowed on the next day.');
});

it('creates new themes on the fly and reuses them ignoring case', function () {
    Day::factory()->onDate('2026-10-12')->create();

    $this->actingAs($this->user)
        ->put(route('days.evening.update', '2026-10-12'), [
            'outcome_note' => 'Pressure, then relief.',
            'resonated' => true,
            'themes' => ['Transition', ' work '],
        ])
        ->assertRedirect();

    $day = Day::where('date', '2026-10-12')->sole();

    expect($day->resonated)->toBeTrue()
        ->and($day->themes->pluck('name')->sort()->values()->all())->toBe(['Transition', 'work'])
        ->and(Theme::count())->toBe(2);

    Day::factory()->onDate('2026-10-11')->create();

    $this->actingAs($this->user)
        ->put(route('days.evening.update', '2026-10-11'), ['themes' => ['transition']])
        ->assertRedirect();

    expect(Theme::count())->toBe(2)
        ->and(Day::where('date', '2026-10-11')->sole()->themes->first()->name)->toBe('Transition');
});

it('lets a card note be saved from any page and keeps it on every day', function () {
    $aeon = card('the-aeon');

    $this->actingAs($this->user)
        ->putJson(route('cards.note.update', $aeon), ['personal_note' => 'Renewal, not an ending.'])
        ->assertNoContent();

    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), ['card_id' => $aeon->id])
        ->assertRedirect();

    $this->actingAs($this->user)
        ->get(route('days.show', '2026-10-12'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DayShow')
            ->where('day.reading.card.personalNote', 'Renewal, not an ending.')
        );
});

it('shows the Tree positions and journal link on the day page', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), ['card_id' => card('the-aeon')->id]);

    $this->actingAs($this->user)
        ->get(route('days.show', '2026-10-12'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DayShow')
            ->where('journalUrl', 'https://750words.com/account/stats/2026-10-12')
            ->where('tree.main.paths', [31])
            ->where('tree.main.nodes', [])
        );
});

it('opens the edit page with locks reported for the day', function () {
    Day::factory()->onDate('2026-10-01')->create();

    $this->actingAs($this->user)
        ->get(route('days.edit', '2026-10-01'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DayEdit')
            ->where('locks.morning', true)
            ->where('locks.evening', true)
            ->where('locks.morningUntil', '2026-10-08')
        );
});

it('rejects a date that does not exist', function () {
    $this->actingAs($this->user)
        ->get('/days/2026-02-30')
        ->assertNotFound();
});

it('saves the theme preference and shares it with the page', function () {
    $this->actingAs($this->user)
        ->patch(route('settings.update'), ['theme' => 'goblin'])
        ->assertRedirect();

    $this->actingAs($this->user)
        ->get(route('today'))
        ->assertSee('<meta name="theme-preference" content="goblin">', false);
});

it('rejects a birth date in the future', function () {
    $this->actingAs($this->user)
        ->patch(route('settings.update'), ['birth_date' => CarbonImmutable::now()->addDay()->toDateString()])
        ->assertSessionHasErrors('birth_date');
});

it('lists birth cards on the settings page once a birth date is set', function () {
    $this->actingAs($this->user)
        ->patch(route('settings.update'), ['birth_date' => '1970-10-28'])
        ->assertRedirect();

    $this->actingAs($this->user)
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings')
            ->where('settings.birthDate', '1970-10-28')
            ->has('birthCards', 6)
            ->where('birthCards.3.name', 'Five of Cups')
        );
});

it('marks a birth card on the card page with its role', function () {
    $this->actingAs($this->user)
        ->patch(route('settings.update'), ['birth_date' => '1970-10-28']);

    $this->actingAs($this->user)
        ->get(route('cards.show', 'death'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('CardShow')
            ->where('card.birthRoles', ['Sun-sign Atu'])
            ->where('position.kind', 'path')
            ->where('position.number', 24)
        );
});

it('lists the days a card appeared on, as main card or jumper or clarifier', function () {
    $this->actingAs($this->user)
        ->put(route('days.update', '2026-10-12'), [
            'card_id' => card('the-aeon')->id,
            'jumper_ids' => [card('the-moon')->id],
        ]);

    $this->actingAs($this->user)
        ->get(route('cards.show', 'the-moon'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('CardShow')
            ->has('appearances', 1)
            ->where('appearances.0.role', 'jumper')
            ->where('appearances.0.date', '2026-10-12')
        );
});
