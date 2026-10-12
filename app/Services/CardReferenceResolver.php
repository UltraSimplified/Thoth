<?php

namespace App\Services;

use App\Models\Card;
use Illuminate\Support\Collection;

/**
 * Turns a card as written in a reading list ("XX", "ATU XX", "EIGHT of WANDS", "Knight of Disks", "The Aeon",
 * "Aoen") into a card. Misspellings are tolerated by edit distance, so names need only be close.
 */
final class CardReferenceResolver
{
    /**
     * Alternative names, mapped to the name the deck uses.
     */
    private const ALIASES = [
        'magician' => 'magus',
        'high priestess' => 'priestess',
        'wheel of fortune' => 'fortune',
        'justice' => 'adjustment',
        'the passion' => 'lust',
    ];

    private const RANK_WORDS = [
        'ace' => 'ace', 'two' => 'two', 'three' => 'three', 'four' => 'four', 'five' => 'five',
        'six' => 'six', 'seven' => 'seven', 'eight' => 'eight', 'nine' => 'nine', 'ten' => 'ten',
        'knight' => 'knight', 'queen' => 'queen', 'prince' => 'prince', 'princess' => 'princess',
        'king' => 'knight', 'page' => 'princess',
    ];

    private const RANK_NUMBERS = [
        1 => 'ace', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
    ];

    private const SUIT_WORDS = [
        'wands' => 'wands', 'wand' => 'wands',
        'cups' => 'cups', 'cup' => 'cups',
        'swords' => 'swords', 'sword' => 'swords',
        'disks' => 'disks', 'disk' => 'disks',
    ];

    private const ROMAN_VALUES = ['I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100];

    /** @var Collection<int, Card>|null */
    private ?Collection $cards = null;

    /**
     * The card a written reference names, or null when nothing is close enough.
     */
    public function resolve(string $reference): ?Card
    {
        $reference = trim($reference);

        if (preg_match('/^(?:atu\s+)?([ivxlc]+|\d{1,2})$/i', $reference, $match) === 1) {
            $number = ctype_digit($match[1]) ? (int) $match[1] : $this->romanToInt($match[1]);

            if ($number !== null && $number >= 0 && $number <= 21) {
                return $this->majors()->firstWhere('number', $number);
            }
        }

        if (preg_match('/^(\S+)\s+(?:of\s+)?(\S+)$/i', $reference, $match) === 1) {
            $rank = $this->rankOf($match[1]);
            $suit = $this->suitOf($match[2]);

            if ($rank !== null && $suit !== null) {
                return $this->minors()->first(
                    fn (Card $card): bool => $card->suit?->value === $suit && $card->rank === $rank,
                );
            }
        }

        return $this->majorNamed($reference);
    }

    /**
     * Whether a written title is the card's own title. Majors are titled by their name, pips by their Thoth title.
     * Courts have no title, so this is false for them.
     */
    public function titleMatches(Card $card, string $title): bool
    {
        $expected = $card->isMajor() ? $card->name : $card->title;

        return $expected !== null && $this->namesMatch($title, $expected);
    }

    /**
     * The major arcana a written title names, if any. Used to spot an Atu number that disagrees with its name.
     */
    public function majorNamed(string $title): ?Card
    {
        $key = $this->nameKey($title);
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($this->majors() as $major) {
            $distance = $this->distance($key, $this->nameKey($major->name));

            if ($distance < $bestDistance) {
                $best = $major;
                $bestDistance = $distance;
            }
        }

        return $best !== null && $bestDistance <= $this->tolerance($key) ? $best : null;
    }

    private function namesMatch(string $written, string $expected): bool
    {
        $key = $this->nameKey($written);

        return $key !== '' && $this->distance($key, $this->nameKey($expected)) <= $this->tolerance($key);
    }

    private function rankOf(string $word): ?string
    {
        $word = mb_strtolower($word);

        if (ctype_digit($word)) {
            return self::RANK_NUMBERS[(int) $word] ?? null;
        }

        return self::RANK_WORDS[$word] ?? $this->closest($word, array_keys(self::RANK_WORDS), self::RANK_WORDS);
    }

    private function suitOf(string $word): ?string
    {
        $word = mb_strtolower($word);

        return self::SUIT_WORDS[$word] ?? $this->closest($word, array_keys(self::SUIT_WORDS), self::SUIT_WORDS);
    }

    /**
     * @param  list<string>  $candidates
     * @param  array<string, string>  $values
     */
    private function closest(string $word, array $candidates, array $values): ?string
    {
        if (mb_strlen($word) < 4) {
            return null;
        }

        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($candidates as $candidate) {
            $distance = $this->distance($word, $candidate);

            if ($distance < $bestDistance) {
                $best = $values[$candidate];
                $bestDistance = $distance;
            }
        }

        return $bestDistance <= 1 ? $best : null;
    }

    /**
     * Lower-case letters and digits only, without a leading "the" and with any alias applied.
     */
    private function nameKey(string $name): string
    {
        $key = mb_strtolower(trim($name));
        $key = preg_replace('/[^a-z0-9 ]+/', '', $key) ?? '';
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? '');
        $key = preg_replace('/^the /', '', $key) ?? '';

        return self::ALIASES[$key] ?? $key;
    }

    /**
     * How many single-character edits (a transposition counts as one) a name may differ by.
     */
    private function tolerance(string $key): int
    {
        return mb_strlen($key) >= 4 ? 2 : 0;
    }

    /**
     * Optimal string alignment distance: insertions, deletions, substitutions and adjacent transpositions.
     */
    private function distance(string $a, string $b): int
    {
        $lengthA = mb_strlen($a);
        $lengthB = mb_strlen($b);
        $charsA = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charsB = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $matrix = [];

        for ($i = 0; $i <= $lengthA; $i++) {
            $matrix[$i][0] = $i;
        }

        for ($j = 0; $j <= $lengthB; $j++) {
            $matrix[0][$j] = $j;
        }

        for ($i = 1; $i <= $lengthA; $i++) {
            for ($j = 1; $j <= $lengthB; $j++) {
                $cost = $charsA[$i - 1] === $charsB[$j - 1] ? 0 : 1;

                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,
                    $matrix[$i][$j - 1] + 1,
                    $matrix[$i - 1][$j - 1] + $cost,
                );

                if ($i > 1 && $j > 1 && $charsA[$i - 1] === $charsB[$j - 2] && $charsA[$i - 2] === $charsB[$j - 1]) {
                    $matrix[$i][$j] = min($matrix[$i][$j], $matrix[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $matrix[$lengthA][$lengthB];
    }

    private function romanToInt(string $roman): ?int
    {
        $roman = strtoupper($roman);
        $total = 0;
        $previous = 0;

        for ($index = strlen($roman) - 1; $index >= 0; $index--) {
            $value = self::ROMAN_VALUES[$roman[$index]] ?? null;

            if ($value === null) {
                return null;
            }

            $total += $value < $previous ? -$value : $value;
            $previous = max($previous, $value);
        }

        return $total;
    }

    /**
     * @return Collection<int, Card>
     */
    private function majors(): Collection
    {
        return $this->allCards()->where('arcana', 'major')->values();
    }

    /**
     * @return Collection<int, Card>
     */
    private function minors(): Collection
    {
        return $this->allCards()->where('arcana', 'minor')->values();
    }

    /**
     * @return Collection<int, Card>
     */
    private function allCards(): Collection
    {
        return $this->cards ??= Card::query()->get();
    }
}
