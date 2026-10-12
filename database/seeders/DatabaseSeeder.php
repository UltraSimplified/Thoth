<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DeckSeeder::class);

        User::factory()->create([
            'name' => 'Robin',
            'email' => 'robin@ultrasimplified.com',
            'password' => 'Sn0fru',
        ]);
    }
}
