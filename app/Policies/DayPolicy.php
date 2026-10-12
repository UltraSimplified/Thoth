<?php

namespace App\Policies;

use App\Models\Day;
use App\Models\User;

/**
 * The 7-day morning lock (spec 4.8). Checked on the server, so a stale form or an offline sync cannot change a
 * locked day. The evening fields have no lock, so they need no policy.
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
}
