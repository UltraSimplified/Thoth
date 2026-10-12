<?php

namespace App\Policies;

use App\Models\Day;
use App\Models\User;

/**
 * The 7-day morning lock and the day-after evening lock (spec 4.8). Checked on the server, so a stale form
 * or an offline sync cannot change a locked day.
 */
class DayPolicy
{
    /**
     * The reading, jumpers, clarifiers and event note can change until the seventh day after the date.
     */
    public function updateMorning(User $user, Day $day): bool
    {
        return ! $day->isLockedOn(today());
    }

    /**
     * The outcome, resonance and themes can change until the end of the day after the date.
     */
    public function updateEvening(User $user, Day $day): bool
    {
        return ! $day->isEveningLockedOn(today());
    }
}
