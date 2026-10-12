<?php

namespace App\Models;

use App\ReadingCardKind;
use Database\Factories\ReadingCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reading_id
 * @property int $card_id
 * @property ReadingCardKind $kind
 * @property int $position
 */
#[Fillable(['reading_id', 'card_id', 'kind', 'position'])]
class ReadingCard extends Model
{
    /** @use HasFactory<ReadingCardFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ReadingCardKind::class,
        ];
    }

    /**
     * @return BelongsTo<Reading, $this>
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(Reading::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
