<?php

namespace Database\Factories;

use App\Models\Card;
use App\Suit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    /**
     * Define the model's default state: a minor Two of Wands. Real cards come from the DeckSeeder.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Card '.fake()->unique()->numerify('####');

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'title' => null,
            'arcana' => 'minor',
            'suit' => Suit::Wands,
            'rank' => 'two',
            'number' => 2,
            'element' => 'fire',
            'thothreadings_url' => 'https://thothreadings.com/blog/'.Str::slug($name).'/',
        ];
    }

    /**
     * A major arcana card, such as The Aeon.
     */
    public function major(int $number = 20): static
    {
        return $this->state(fn (): array => [
            'arcana' => 'major',
            'suit' => null,
            'rank' => null,
            'number' => $number,
            'element' => null,
        ]);
    }
}
