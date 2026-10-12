<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\DayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property string|null $event_note
 * @property string|null $outcome_note
 * @property bool|null $resonated
 */
#[Fillable(['date', 'event_note', 'outcome_note', 'resonated'])]
class Day extends Model
{
    /** @use HasFactory<DayFactory> */
    use HasFactory;

    /**
     * Morning fields (the reading, its jumpers, clarifiers and the event note) stay editable for this many days after the date.
     */
    public const MORNING_EDIT_DAYS = 7;

    /**
     * Evening fields (outcome, resonance and themes) stay editable for this many days after the date.
     */
    public const EVENING_EDIT_DAYS = 1;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'resonated' => 'boolean',
        ];
    }

    /**
     * @return HasOne<Reading, $this>
     */
    public function reading(): HasOne
    {
        return $this->hasOne(Reading::class);
    }

    /**
     * @return BelongsToMany<Theme, $this>
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class);
    }

    /**
     * Whether the morning fields are read-only on the given day. Locks at midnight at the end of the seventh day after the date.
     */
    public function isLockedOn(CarbonInterface $today): bool
    {
        return $this->editableUntil(self::MORNING_EDIT_DAYS) < $today->toDateString();
    }

    /**
     * Whether the outcome, resonance and themes are read-only on the given day. Locks at midnight at the end of the following day.
     */
    public function isEveningLockedOn(CarbonInterface $today): bool
    {
        return $this->editableUntil(self::EVENING_EDIT_DAYS) < $today->toDateString();
    }

    /**
     * The last date (inclusive) on which the given group of fields can still change.
     */
    public function editableUntil(int $days): string
    {
        return $this->date->copy()->addDays($days)->toDateString();
    }
}
