<?php

namespace App\Http\Controllers;

use App\Http\Requests\RestoreBackupRequest;
use App\Services\BackupRestorer;
use Illuminate\Http\RedirectResponse;

class RestoreController extends Controller
{
    /**
     * Restore an uploaded backup, then return to the settings page with a summary.
     */
    public function store(RestoreBackupRequest $request, BackupRestorer $restorer): RedirectResponse
    {
        $counts = $restorer->restore($request->backup());

        return redirect()
            ->route('settings')
            ->with('status', 'Restored '.$counts['days'].' days and '.$counts['card_notes'].' card notes.');
    }
}
