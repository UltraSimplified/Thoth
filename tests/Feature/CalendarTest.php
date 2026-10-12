<?php

use App\Models\Card;
use App\Models\Day;
use App\Models\Theme;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DeckSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DeckSeeder::class);
    Date::setTestNow(CarbonImmutable::parse('2026-10-12 09:00', 'Europe/London'));
    $this->user = User::factory()->create();
    Storage::fake('public');
});

afterEach(fn () => Date::setTestNow());

function readingOn(string $date, string $slug, array $details = []): Day
{
    $day = Day::factory()->onDate($date)->create();
    $day->reading()->create([
        'card_id' => Card::where('slug', $slug)->sole()->id,
        'question' => $details['question'] ?? null,
        'interpretation_notes' => $details['notes'] ?? null,
    ]);

    return $day;
}

/**
 * @return list<string>
 */
function listedDates(TestResponse $response): array
{
    return collect($response->viewData('page')['props']['days']['data'])->pluck('date')->all();
}

it('redirects the calendar to the current month', function () {
    $this->actingAs($this->user)
        ->get('/calendar')
        ->assertRedirect('/calendar/2026/10');
});

it('builds a Monday-first month grid covering the whole month', function () {
    $this->actingAs($this->user)
        ->get('/calendar/2026/10')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar')
            ->where('view', 'grid')
            ->where('month.label', 'October 2026')
            ->has('weeks', 5)
            ->where('weeks.0.0.date', '2026-09-28')
            ->where('weeks.4.6.date', '2026-11-01')
        );
});

it('marks a reading with its jumpers and clarifiers, and a note-only day, in the grid', function () {
    $day = readingOn('2026-10-05', 'the-aeon', ['question' => 'What is ending?']);
    $day->reading->jumpers()->create(['card_id' => Card::where('slug', 'the-moon')->sole()->id, 'kind' => 'jumper', 'position' => 1]);
    $day->reading->clarifiers()->create(['card_id' => Card::where('slug', 'seven-of-disks')->sole()->id, 'kind' => 'clarifier', 'position' => 1]);
    Day::factory()->onDate('2026-10-06')->create(['event_note' => 'Met a friend.']);

    $response = $this->actingAs($this->user)->get('/calendar/2026/10');

    $cells = collect($response->viewData('page')['props']['weeks'])->flatten(1)->keyBy('date');

    expect($cells['2026-10-05']['card']['name'])->toBe('The Aeon')
        ->and($cells['2026-10-05']['jumpers'])->toBe(1)
        ->and($cells['2026-10-05']['clarifiers'])->toBe(1)
        ->and($cells['2026-10-05']['url'])->toBe(route('days.show', '2026-10-05'))
        ->and($cells['2026-10-06']['noteOnly'])->toBeTrue()
        ->and($cells['2026-10-07']['url'])->toBe(route('days.edit', '2026-10-07'))
        ->and($cells['2026-10-07']['card'])->toBeNull();
});

it('lists days newest first with the card, question and event note', function () {
    readingOn('2026-10-01', 'the-aeon', ['question' => 'Renewal?']);
    readingOn('2026-10-02', 'the-tower');

    $response = $this->actingAs($this->user)->get('/calendar/2026/10?view=list');

    expect(listedDates($response))->toBe(['2026-10-02', '2026-10-01'])
        ->and($response->viewData('page')['props']['days']['data'][1]['question'])->toBe('Renewal?');
});

it('filters the list by suit, arcana, element, planet and sign of the main card', function (string $query, array $expected) {
    readingOn('2026-10-01', 'the-aeon');       // major, fire, no planet
    readingOn('2026-10-02', 'five-of-cups');   // water, Mars, Scorpio
    readingOn('2026-10-03', 'the-tower');      // major, Mars
    readingOn('2026-10-04', 'seven-of-wands'); // wands, fire

    expect(listedDates($this->actingAs($this->user)->get('/calendar/2026/10?view=list&'.$query)))->toBe($expected);
})->with([
    ['suit=wands', ['2026-10-04']],
    ['arcana=major', ['2026-10-03', '2026-10-01']],
    ['element=water', ['2026-10-02']],
    ['planet=Mars', ['2026-10-04', '2026-10-03', '2026-10-02']],
    ['sign=Scorpio', ['2026-10-02']],
]);

it('searches questions and notes and filters by resonance and theme', function () {
    $aeon = readingOn('2026-10-01', 'the-aeon', ['question' => 'Where is the renewal?']);
    $aeon->update(['resonated' => true]);
    $aeon->themes()->attach(Theme::create(['name' => 'Transition']));

    readingOn('2026-10-02', 'the-tower', ['notes' => 'Structures fall.']);

    expect(listedDates($this->actingAs($this->user)->get('/calendar/2026/10?view=list&q=renewal')))->toBe(['2026-10-01'])
        ->and(listedDates($this->actingAs($this->user)->get('/calendar/2026/10?view=list&q=structures')))->toBe(['2026-10-02'])
        ->and(listedDates($this->actingAs($this->user)->get('/calendar/2026/10?view=list&resonance=yes&theme=transition')))->toBe(['2026-10-01']);
});

it('rejects an unknown suit in the list filters', function () {
    $this->actingAs($this->user)
        ->get('/calendar/2026/10?view=list&suit=pentacles')
        ->assertSessionHasErrors('suit');
});

it('flags only the days after today, so only empty future tiles show the card-back', function () {
    $response = $this->actingAs($this->user)->get('/calendar/2026/10');

    $cells = collect($response->viewData('page')['props']['weeks'])->flatten(1)->keyBy('date');

    expect($cells['2026-10-13']['isFuture'])->toBeTrue()
        ->and($cells['2026-10-12']['isFuture'])->toBeFalse()
        ->and($cells['2026-10-11']['isFuture'])->toBeFalse()
        ->and($response->viewData('page')['props']['cardBackUrl'])->toEndWith('/images/card-back.svg');
});
