<?php

use App\Models\User;

it('redirects guests to the login page', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'today', 'calendar', 'stats', 'cards', 'notes', 'themes', 'imports', 'settings',
]);

it('returns a successful response for a logged-in user', function (string $routeName) {
    $this->actingAs(User::factory()->create())
        ->get(route($routeName))
        ->assertOk();
})->with([
    'today', 'stats', 'cards', 'notes', 'themes', 'imports', 'settings',
]);
