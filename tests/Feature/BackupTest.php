<?php

use App\Models\Card;
use App\Models\Day;
use App\Models\ReadingCard;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\DeckSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(DeckSeeder::class);
    $this->user = User::factory()->create();
});

/**
 * A day with a reading, a jumper, a theme and a card note, to export and restore.
 */
function recordedDay(): Day
{
    $day = Day::factory()->onDate('2026-02-05')->create([
        'event_note' => 'Post late',
        'resonated' => true,
    ]);

    $reading = $day->reading()->create([
        'card_id' => Card::where('slug', 'the-tower')->sole()->id,
        'question' => 'What breaks?',
        'interpretation_notes' => 'Air unchallenged',
    ]);

    ReadingCard::create([
        'reading_id' => $reading->id,
        'card_id' => Card::where('slug', 'the-moon')->sole()->id,
        'kind' => 'jumper',
        'position' => 1,
    ]);

    $day->themes()->attach(Theme::create(['name' => 'Work']));

    Card::where('slug', 'the-aeon')->update(['personal_note' => 'Renewal']);

    return $day;
}

it('exports days, readings, jumpers, clarifiers, themes and card notes as JSON', function () {
    recordedDay();

    $json = $this->actingAs($this->user)
        ->get(route('export.json'))
        ->assertOk()
        ->assertHeader('Content-Disposition')
        ->json();

    expect($json['version'])->toBe(1)
        ->and($json['days'])->toHaveCount(1)
        ->and($json['days'][0])->toMatchArray([
            'date' => '2026-02-05',
            'event_note' => 'Post late',
            'resonated' => true,
            'themes' => ['Work'],
            'reading' => [
                'card' => 'the-tower',
                'question' => 'What breaks?',
                'interpretation_notes' => 'Air unchallenged',
                'jumpers' => ['the-moon'],
                'clarifiers' => [],
            ],
        ])
        ->and($json['card_notes'])->toBe([['card' => 'the-aeon', 'note' => 'Renewal']]);
});

it('exports days as CSV with one row per day', function () {
    recordedDay();

    $csv = $this->actingAs($this->user)->get(route('export.days'))->assertOk()->getContent();

    $rows = array_map('str_getcsv', explode("\n", trim($csv)));

    expect($rows[0])->toBe(['date', 'event_note', 'outcome_note', 'resonated', 'card', 'question', 'interpretation_notes', 'jumpers', 'clarifiers', 'themes'])
        ->and($rows)->toHaveCount(2)
        ->and($rows[1])->toBe(['2026-02-05', 'Post late', '', 'yes', 'the-tower', 'What breaks?', 'Air unchallenged', 'the-moon', '', 'Work']);
});

it('exports card notes as CSV', function () {
    recordedDay();

    $csv = $this->actingAs($this->user)->get(route('export.card-notes'))->assertOk()->getContent();

    expect(array_map('str_getcsv', explode("\n", trim($csv))))->toBe([
        ['card', 'name', 'note'],
        ['the-aeon', 'The Aeon', 'Renewal'],
    ]);
});

it('restores a backup this app exported, bringing back the same days and notes', function () {
    recordedDay();
    $backup = $this->actingAs($this->user)->get(route('export.json'))->getContent();

    Day::query()->delete();
    Card::query()->update(['personal_note' => null]);

    $this->actingAs($this->user)
        ->post(route('restore.store'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $backup)])
        ->assertRedirect(route('settings'))
        ->assertSessionHas('status', 'Restored 1 days and 1 card notes.');

    $day = Day::where('date', '2026-02-05')->sole();

    expect($day->event_note)->toBe('Post late')
        ->and($day->resonated)->toBeTrue()
        ->and($day->themes->pluck('name')->all())->toBe(['Work'])
        ->and($day->reading->card->slug)->toBe('the-tower')
        ->and($day->reading->jumpers->map(fn ($entry) => $entry->card->slug)->all())->toBe(['the-moon'])
        ->and(Card::where('slug', 'the-aeon')->sole()->personal_note)->toBe('Renewal');
});

it('restores over an existing day with the same date', function () {
    recordedDay();
    $backup = $this->actingAs($this->user)->get(route('export.json'))->getContent();

    Day::where('date', '2026-02-05')->sole()->update(['event_note' => 'Changed since']);

    $this->actingAs($this->user)
        ->post(route('restore.store'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $backup)])
        ->assertRedirect();

    expect(Day::where('date', '2026-02-05')->count())->toBe(1)
        ->and(Day::where('date', '2026-02-05')->sole()->event_note)->toBe('Post late');
});

it('refuses a backup that names a card this deck does not have, and writes nothing', function () {
    $backup = json_encode([
        'version' => 1,
        'days' => [[
            'date' => '2026-02-05',
            'event_note' => null,
            'reading' => ['card' => 'the-ghost', 'jumpers' => [], 'clarifiers' => []],
            'themes' => [],
        ]],
        'card_notes' => [],
    ]);

    $this->actingAs($this->user)
        ->post(route('restore.store'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $backup)])
        ->assertSessionHasErrors('backup');

    expect(Day::count())->toBe(0);
});

it('refuses a file that is not JSON', function () {
    $this->actingAs($this->user)
        ->post(route('restore.store'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', 'not a backup')])
        ->assertSessionHasErrors('backup');
});

it('refuses a backup from a version it does not understand', function () {
    $backup = json_encode(['version' => 99, 'days' => [], 'card_notes' => []]);

    $this->actingAs($this->user)
        ->post(route('restore.store'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $backup)])
        ->assertSessionHasErrors('version');
});
