<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the owner account with the password from the environment', function () {
    config(['seeding.owner_password' => 'a-test-password']);

    $this->seed(DatabaseSeeder::class);

    $owner = User::where('email', 'robin@ultrasimplified.com')->sole();

    expect(Hash::check('a-test-password', $owner->password))->toBeTrue();
});

it('refuses to create the owner account when no password is configured', function () {
    config(['seeding.owner_password' => null]);

    expect(fn () => $this->seed(DatabaseSeeder::class))
        ->toThrow(RuntimeException::class, 'SEED_OWNER_PASSWORD');

    expect(User::count())->toBe(0);
});
