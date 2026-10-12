<?php

namespace App\Models;

use App\Sephira;
use App\Suit;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $title
 * @property string $arcana
 * @property Suit|null $suit
 * @property string|null $rank
 * @property int $number
 * @property string|null $element
 * @property string|null $sub_element
 * @property string|null $hebrew_letter
 * @property string|null $planet
 * @property string|null $sign
 * @property int|null $decan
 * @property string|null $zodiac_span_from
 * @property string|null $zodiac_span_to
 * @property string|null $quadrant
 * @property Sephira|null $sephira
 * @property int|null $tree_path
 * @property string $thothreadings_url
 * @property string|null $personal_note
 */
#[Fillable(['slug', 'name', 'title', 'arcana', 'suit', 'rank', 'number', 'element', 'sub_element', 'hebrew_letter', 'planet', 'sign', 'decan', 'zodiac_span_from', 'zodiac_span_to', 'quadrant', 'sephira', 'tree_path', 'thothreadings_url', 'personal_note'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    /**
     * The route key used by the card pages, e.g. /cards/the-aeon.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suit' => Suit::class,
            'sephira' => Sephira::class,
        ];
    }

    /**
     * @return HasMany<ReadingCard, $this>
     */
    public function readingCards(): HasMany
    {
        return $this->hasMany(ReadingCard::class);
    }

    public function isMajor(): bool
    {
        return $this->arcana === 'major';
    }

    public function isCourt(): bool
    {
        return in_array($this->rank, ['princess', 'prince', 'queen', 'knight'], true);
    }

    /**
     * The card's number written in Roman numerals, for majors only (e.g. XX for The Aeon).
     */
    public function romanNumeral(): ?string
    {
        if (! $this->isMajor()) {
            return null;
        }

        if ($this->number === 0) {
            return '0';
        }

        $remaining = $this->number;
        $numerals = [
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];
        $roman = '';

        foreach ($numerals as $value => $symbol) {
            while ($remaining >= $value) {
                $roman .= $symbol;
                $remaining -= $value;
            }
        }

        return $roman;
    }

    /**
     * Full-size card image URL, or the card-back placeholder while the scan is missing.
     */
    public function imageUrl(): string
    {
        return $this->imageUrlFor('cards/'.$this->slug.'.webp');
    }

    /**
     * Thumbnail card image URL, or the card-back placeholder while the scan is missing.
     */
    public function thumbnailUrl(): string
    {
        return $this->imageUrlFor('cards/thumbs/'.$this->slug.'.webp');
    }

    private function imageUrlFor(string $path): string
    {
        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : asset('images/card-back.svg');
    }
}
