<?php

namespace Database\Seeders;

use App\Models\Card;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DeckSeeder extends Seeder
{
    /**
     * Seed the 78 cards of the Thoth deck from database/seeders/data/deck.json.
     *
     * Re-running updates the attributions in place by slug. Personal notes are left untouched.
     */
    public function run(): void
    {
        /** @var array{cards: list<array<string, mixed>>} $deck */
        $deck = json_decode(
            File::get(database_path('seeders/data/deck.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($deck['cards'] as $card) {
            Card::query()->updateOrCreate(
                ['slug' => $card['slug']],
                $this->attributes($card),
            );
        }
    }

    /**
     * Map one deck entry from the JSON file onto the cards table columns.
     *
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function attributes(array $card): array
    {
        $tree = $card['tree'];
        $span = $card['zodiac_span'];

        return [
            'name' => $card['name'],
            'title' => $card['title'],
            'arcana' => $card['arcana'],
            'suit' => $card['suit'],
            'rank' => $card['rank'],
            'number' => $card['number'],
            'element' => $card['element'],
            'sub_element' => $card['sub_element'],
            'hebrew_letter' => $card['hebrew_letter'],
            'planet' => $card['planet'],
            'sign' => $card['sign'],
            'decan' => $card['decan'],
            'zodiac_span_from' => $span['from'] ?? null,
            'zodiac_span_to' => $span['to'] ?? null,
            'quadrant' => $card['quadrant'],
            'sephira' => $tree['type'] === 'sephira' ? $tree['sephira'] : null,
            'tree_path' => $tree['type'] === 'path' ? $tree['number'] : null,
            'thothreadings_url' => $card['url'],
        ];
    }
}
