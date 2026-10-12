<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Reading;
use App\Models\ReadingCard;
use App\ReadingCardKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingCard>
 */
class ReadingCardFactory extends Factory
{
    /**
     * Define the model's default state: the first jumper of a reading.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reading_id' => Reading::factory(),
            'card_id' => Card::factory(),
            'kind' => ReadingCardKind::Jumper,
            'position' => 1,
        ];
    }

    /**
     * A clarifier at the given position.
     */
    public function clarifier(int $position = 1): static
    {
        return $this->state(fn (): array => [
            'kind' => ReadingCardKind::Clarifier,
            'position' => $position,
        ]);
    }
}
