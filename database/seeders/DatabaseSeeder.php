<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * @throws RuntimeException when SEED_OWNER_PASSWORD is not set
     */
    public function run(): void
    {
        $this->call(DeckSeeder::class);

        $password = config('seeding.owner_password');

        if (blank($password)) {
            throw new RuntimeException('Set SEED_OWNER_PASSWORD in .env before seeding the owner account.');
        }

        User::factory()->create([
            'name' => 'Robin',
            'email' => 'robin@ultrasimplified.com',
            'password' => $password,
        ]);
    }
}
