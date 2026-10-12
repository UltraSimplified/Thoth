<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Day;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reading>
 */
class ReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_id' => Day::factory(),
            'card_id' => Card::factory(),
            'question' => null,
            'interpretation_notes' => null,
        ];
    }
}
