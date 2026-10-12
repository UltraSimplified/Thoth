<?php

use App\Models\Card;
use App\Models\Day;
use App\Models\Reading;
use App\Models\User;
use App\Services\ReadingImporter;
use Database\Seeders\DeckSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(DeckSeeder::class));

function parseReadings(string $text, int $year = 2026): array
{
    return app(ReadingImporter::class)->parse($text, $year);
}

function cardNamed(string $slug): Card
{
    return Card::where('slug', $slug)->sole();
}

it('reads the example list from the spec into one row per day', function () {
    $text = <<<'TEXT'
* FEBRUARY (4 Cups, 10 Disks, ...) = DISKS
   * XVI — The Tower / Knight of Disks — 05/02
   * EIGHT of WANDS — Swiftness 08/02
   * SIX of WANDS — Victory — 3 Jun
      * ATU XX - The Aeon
TEXT;

    $rows = parseReadings($text);

    expect($rows)->toHaveCount(3)
        ->and($rows[0]['date'])->toBe('2026-02-05')
        ->and($rows[0]['card_id'])->toBe(cardNamed('the-tower')->id)
        ->and($rows[0]['clarifier_ids'])->toBe([cardNamed('knight-of-disks')->id])
        ->and($rows[0]['problems'])->toBe([])
        ->and($rows[1]['date'])->toBe('2026-02-08')
        ->and($rows[1]['card_id'])->toBe(cardNamed('eight-of-wands')->id)
        ->and($rows[1]['problems'])->toBe([])
        ->and($rows[2]['date'])->toBe('2026-06-03')
        ->and($rows[2]['card_id'])->toBe(cardNamed('six-of-wands')->id)
        ->and($rows[2]['clarifier_ids'])->toBe([cardNamed('the-aeon')->id])
        ->and($rows[2]['problems'])->toBe([]);
});

it('uses the year chosen on the import screen', function () {
    $rows = parseReadings("* FEBRUARY\n   * EIGHT of WANDS — Swiftness 08/02", 2024);

    expect($rows[0]['date'])->toBe('2024-02-08');
});

it('puts bracketed text into the interpretation notes', function () {
    $rows = parseReadings("   * SIX of WANDS — Victory — 3 Jun (Air unchallenged)\n   * IX — The Hermit 01/01 [0 = 2]");

    expect($rows[0]['notes'])->toBe('Air unchallenged')
        ->and($rows[1]['notes'])->toBe('0 = 2')
        ->and($rows[1]['card_id'])->toBe(cardNamed('the-hermit')->id);
});

it('flags a title that is not the card’s title', function () {
    $rows = parseReadings('   * SEVEN of DISKS — Success 04/02');

    expect($rows[0]['problems'])->toHaveCount(1)
        ->and($rows[0]['problems'][0])->toContain('“Success” is not “Failure”');
});

it('flags an Atu number whose name belongs to another card', function () {
    $rows = parseReadings('   * XVI — The Universe 05/02');

    expect($rows[0]['problems'][0])->toStartWith('Atu number and name disagree: XVI is The Tower, not The Universe.');
});

it('flags a card with no date', function () {
    $rows = parseReadings('   * EIGHT of WANDS — Swiftness');

    expect($rows[0]['problems'])->toBe(['Card with no date.']);
});

it('keeps a date with no card as a note-only day', function () {
    $rows = parseReadings('Met an old friend 12/02');

    expect($rows[0]['card_id'])->toBeNull()
        ->and($rows[0]['date'])->toBe('2026-02-12')
        ->and($rows[0]['event_note'])->toBe('Met an old friend')
        ->and($rows[0]['problems'][0])->toStartWith('Date with no card');
});

it('flags a line it cannot read at all', function () {
    $rows = parseReadings('   * something odd happened');

    expect($rows[0]['problems'])->toBe(['Unrecognised line.']);
});

it('flags a date that does not exist', function () {
    $rows = parseReadings('   * EIGHT of WANDS — Swiftness 31/02');

    expect($rows[0]['problems'])->toContain('That date does not exist in 2026.');
});

it('flags a second line for a date already used', function () {
    $rows = parseReadings("   * EIGHT of WANDS — Swiftness 08/02\n   * NINE of WANDS — Strength 08/02");

    expect($rows[1]['problems'][0])->toBe('Same date as line 1.');
});

it('flags a clarifier that is not a card', function () {
    $rows = parseReadings('   * XVI — The Tower / Not a card 05/02');

    expect($rows[0]['problems'][0])->toStartWith('Clarifier not recognised');
});

it('accepts misspelt names and number words and numerals', function (string $line, string $slug) {
    $rows = parseReadings("   * {$line} 02/03");

    expect($rows[0]['card_id'])->toBe(cardNamed($slug)->id)
        ->and($rows[0]['problems'])->toBe([]);
})->with([
    ['Aoen', 'the-aeon'],
    ['Heirophant', 'the-hierophant'],
    ['ATU XX', 'the-aeon'],
    ['XX', 'the-aeon'],
    ['20', 'the-aeon'],
    ['EIGHT of WANDS', 'eight-of-wands'],
    ['5 Wands', 'five-of-wands'],
    ['Knight of Disks', 'knight-of-disks'],
    ['The Magician', 'the-magus'],
]);

it('shows the review screen with rows already recorded left unconfirmed', function () {
    $user = User::factory()->create();
    Day::factory()->onDate('2026-02-08')->create()->reading()->create(['card_id' => cardNamed('ace-of-cups')->id]);

    $this->actingAs($user)
        ->post(route('imports.review'), [
            'text' => "   * EIGHT of WANDS — Swiftness 08/02\n   * SIX of WANDS — Victory 09/02",
            'year' => 2026,
        ])
        ->assertInertia(fn (Assert $page) => $page
            ->component('Import')
            ->where('review.rows.0.include', false)
            ->where('review.rows.0.problems.0', 'This day already has a reading. Confirming replaces it.')
            ->where('review.rows.1.include', true)
        );
});

it('saves only the confirmed days, including dates older than the lock', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imports.store'), [
            'days' => [
                [
                    'date' => '2025-03-01',
                    'card_id' => cardNamed('the-aeon')->id,
                    'jumper_ids' => [],
                    'clarifier_ids' => [cardNamed('the-moon')->id],
                    'interpretation_notes' => 'Air unchallenged',
                    'event_note' => null,
                ],
            ],
        ])
        ->assertRedirect(route('imports'));

    $day = Day::where('date', '2025-03-01')->sole();

    expect($day->reading->card->slug)->toBe('the-aeon')
        ->and($day->reading->interpretation_notes)->toBe('Air unchallenged')
        ->and($day->reading->clarifiers->map(fn ($entry) => $entry->card->slug)->all())->toBe(['the-moon'])
        ->and(Reading::count())->toBe(1);
});

it('rejects an import with a card that does not exist', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('imports.store'), [
            'days' => [['date' => '2026-02-05', 'card_id' => 9999]],
        ])
        ->assertSessionHasErrors('days.0.card_id');
});

it('reads dash bullets and skips month headers', function () {
    $rows = parseReadings("- JANUARY (5 Cups, 2 Atu) = CUPS\n  - EMPRESS — 14/1\n  - FOUR of SWORDS — Truce — 03/02");

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['date'])->toBe('2026-01-14')
        ->and($rows[0]['card_id'])->toBe(cardNamed('the-empress')->id)
        ->and($rows[1]['date'])->toBe('2026-02-03')
        ->and($rows[1]['card_id'])->toBe(cardNamed('four-of-swords')->id)
        ->and($rows[0]['problems'])->toBe([])
        ->and($rows[1]['problems'])->toBe([]);
});

it('reads a date written month first', function () {
    $rows = parseReadings('  - FOUR of SWORDS — Truce — October 1');

    expect($rows[0]['date'])->toBe('2026-10-01')
        ->and($rows[0]['problems'])->toBe([]);
});

it('reads a Roman numeral written after the name', function () {
    $rows = parseReadings("  - THE HERMIT IX — 15/1\n  - LUST XI — 25/1");

    expect($rows[0]['card_id'])->toBe(cardNamed('the-hermit')->id)
        ->and($rows[1]['card_id'])->toBe(cardNamed('lust')->id)
        ->and($rows[0]['problems'])->toBe([]);
});

it('keeps "0 = 2" as a note rather than a title', function () {
    $rows = parseReadings('  - ACE of CUPS — 0 = 2  09/02');

    expect($rows[0]['notes'])->toBe('0 = 2')
        ->and($rows[0]['problems'])->toBe([]);
});

it('nests a dash-bulleted card beneath the day above it as a clarifier', function () {
    $rows = parseReadings("  - EIGHT of CUPS — Indolence 23/03\n    - XV — The Devil");

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['clarifier_ids'])->toBe([cardNamed('the-devil')->id])
        ->and($rows[0]['problems'])->toBe([]);
});
