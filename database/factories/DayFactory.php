<?php

namespace Database\Factories;

use App\Models\Day;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Day>
 */
class DayFactory extends Factory
{
    /**
     * Define the model's default state: a note-only day in the last thirty days.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => CarbonImmutable::today()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'event_note' => null,
            'outcome_note' => null,
            'resonated' => null,
        ];
    }

    /**
     * A day on a specific date (YYYY-MM-DD).
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (): array => ['date' => $date]);
    }
}
