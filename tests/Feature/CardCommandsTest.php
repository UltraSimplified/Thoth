<?php

use App\Models\Card;
use Database\Seeders\DeckSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(DeckSeeder::class));

it('passes the link check when every card page resolves', function () {
    Http::fake(['*' => Http::response('', 200)]);

    $this->artisan('cards:verify-links')
        ->expectsOutputToContain('All 78 card links resolve.')
        ->assertExitCode(0);
});

it('reports each card page that no longer resolves and fails', function () {
    Http::fake(fn ($request) => str_contains($request->url(), 'seven-of-disks')
        ? Http::response('', 404)
        : Http::response('', 200));

    $this->artisan('cards:verify-links')
        ->expectsOutputToContain('Seven of Disks')
        ->expectsOutputToContain('1 of 78 card links did not resolve.')
        ->assertExitCode(1);
});

it('retries a link that refuses HEAD with GET', function () {
    Http::fake([
        'thothreadings.com/blog/the-aeon*' => Http::sequence()
            ->push('', 405)
            ->push('', 200),
        '*' => Http::response('', 200),
    ]);

    $this->artisan('cards:verify-links')->assertExitCode(0);
});

it('stores a cropped full image and thumbnail for each matching scan', function () {
    Storage::fake('public');

    $directory = storage_path('framework/testing/scans-'.uniqid());
    File::makeDirectory($directory, 0755, true);

    $scan = imagecreatetruecolor(600, 1000);
    imagepng($scan, $directory.'/The Aeon.png');
    imagepng($scan, $directory.'/Mystery.png');

    $this->artisan('cards:import-images', ['dir' => $directory])
        ->expectsOutputToContain('Imported 1 card image.')
        ->expectsOutputToContain('No card matches: Mystery.png')
        ->assertExitCode(0);

    Storage::disk('public')->assertExists('cards/the-aeon.webp');
    Storage::disk('public')->assertExists('cards/thumbs/the-aeon.webp');

    $full = getimagesizefromstring(Storage::disk('public')->get('cards/the-aeon.webp'));
    $thumbnail = getimagesizefromstring(Storage::disk('public')->get('cards/thumbs/the-aeon.webp'));

    expect($full[0])->toBe(800)
        ->and($thumbnail[0])->toBe(200)
        ->and(Card::where('slug', 'the-aeon')->sole()->imageUrl())->toContain('/storage/cards/the-aeon.webp');

    File::deleteDirectory($directory);
});

it('serves the card-back placeholder shipped with the app', function () {
    expect(file_exists(public_path('images/card-back.svg')))->toBeTrue();
});
