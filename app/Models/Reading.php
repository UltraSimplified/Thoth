<?php

namespace App\Models;

use App\ReadingCardKind;
use Database\Factories\ReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $day_id
 * @property int $card_id
 * @property string|null $question
 * @property string|null $interpretation_notes
 */
#[Fillable(['day_id', 'card_id', 'question', 'interpretation_notes'])]
class Reading extends Model
{
    /** @use HasFactory<ReadingFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Day, $this>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Jumpers in the order they appeared while shuffling.
     *
     * @return HasMany<ReadingCard, $this>
     */
    public function jumpers(): HasMany
    {
        return $this->cardsOfKind(ReadingCardKind::Jumper);
    }

    /**
     * Clarifiers in the order they were drawn.
     *
     * @return HasMany<ReadingCard, $this>
     */
    public function clarifiers(): HasMany
    {
        return $this->cardsOfKind(ReadingCardKind::Clarifier);
    }

    /**
     * @return HasMany<ReadingCard, $this>
     */
    private function cardsOfKind(ReadingCardKind $kind): HasMany
    {
        return $this->hasMany(ReadingCard::class)
            ->where('kind', $kind)
            ->orderBy('position')
            ->with('card');
    }
}
