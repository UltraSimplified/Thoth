<?php

namespace App\Http\Controllers;

use App\Services\DayPage;
use Inertia\Inertia;
use Inertia\Response;

class TodayController extends Controller
{
    /**
     * The Today screen: the morning form before a card is logged, then the reading and the evening section.
     */
    public function show(DayPage $dayPage): Response
    {
        return Inertia::render('Today', $dayPage->props(today()));
    }
}
