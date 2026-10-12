<?php

use App\Models\Card;
use App\Models\User;
use Database\Seeders\DeckSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DeckSeeder::class);
    $this->user = User::factory()->create();
    Storage::fake('public');
});

it('replaces a card image with an uploaded scan, cropped and resized like an import', function () {
    $this->actingAs($this->user)
        ->post(route('cards.image.update', 'the-aeon'), [
            'image' => UploadedFile::fake()->image('scan.png', 600, 1000),
        ])
        ->assertRedirect();

    Storage::disk('public')->assertExists('cards/the-aeon.webp');
    Storage::disk('public')->assertExists('cards/thumbs/the-aeon.webp');

    $full = getimagesizefromstring(Storage::disk('public')->get('cards/the-aeon.webp'));
    $thumbnail = getimagesizefromstring(Storage::disk('public')->get('cards/thumbs/the-aeon.webp'));

    expect($full[0])->toBe(800)
        ->and($thumbnail[0])->toBe(200);
});

it('serves the new image from a new address so the browser does not keep the old one', function () {
    Storage::disk('public')->put('cards/the-aeon.webp', 'old scan');
    $before = Card::where('slug', 'the-aeon')->sole()->imageUrl();

    $this->actingAs($this->user)
        ->post(route('cards.image.update', 'the-aeon'), [
            'image' => UploadedFile::fake()->image('scan.png', 600, 1000),
        ]);

    $after = Card::where('slug', 'the-aeon')->sole()->imageUrl();

    expect($before)->toEndWith('/storage/cards/the-aeon.webp?v=0')
        ->and($after)->toEndWith('/storage/cards/the-aeon.webp?v=1');
});

it('refuses a file that is not an image and keeps the existing image', function () {
    Storage::disk('public')->put('cards/the-aeon.webp', 'old scan');

    $this->actingAs($this->user)
        ->post(route('cards.image.update', 'the-aeon'), [
            'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('image');

    expect(Storage::disk('public')->get('cards/the-aeon.webp'))->toBe('old scan');
});

it('refuses an image that cannot be decoded', function () {
    $this->actingAs($this->user)
        ->post(route('cards.image.update', 'the-aeon'), [
            'image' => UploadedFile::fake()->createWithContent('truncated.png', "\x89PNG\r\n\x1a\n"),
        ])
        ->assertSessionHasErrors('image');

    Storage::disk('public')->assertMissing('cards/the-aeon.webp');
});

it('requires a signed-in user to replace a card image', function () {
    $this->post(route('cards.image.update', 'the-aeon'), [
        'image' => UploadedFile::fake()->image('scan.png', 600, 1000),
    ])->assertRedirect(route('login'));

    Storage::disk('public')->assertMissing('cards/the-aeon.webp');
});
