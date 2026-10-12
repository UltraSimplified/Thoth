<?php

use App\Models\User;
use Illuminate\Support\Env;
use Inertia\Testing\AssertableInertia as Assert;

it('presents the application as Thoth', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('cards'))
        ->assertOk()
        ->assertSee('<title>Thoth</title>', false)
        ->assertInertia(fn (Assert $page) => $page->where('name', 'Thoth'));
});

it('defaults the application name to Thoth when none is configured', function () {
    $configured = Env::get('APP_NAME');
    Env::getRepository()->clear('APP_NAME');

    try {
        expect(Env::get('APP_NAME'))->toBeNull()
            ->and((require config_path('app.php'))['name'])->toBe('Thoth');
    } finally {
        Env::getRepository()->set('APP_NAME', $configured);
    }
});
