<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DayController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\RestoreController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TodayController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [TodayController::class, 'show'])->name('today');

    Route::get('/days/{date}', [DayController::class, 'show'])
        ->name('days.show')
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::get('/days/{date}/edit', [DayController::class, 'edit'])
        ->name('days.edit')
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::put('/days/{date}', [DayController::class, 'update'])
        ->name('days.update')
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::put('/days/{date}/evening', [DayController::class, 'updateEvening'])
        ->name('days.evening.update')
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');

    Route::get('/calendar', [CalendarController::class, 'current'])->name('calendar');
    Route::get('/calendar/{year}/{month}', [CalendarController::class, 'show'])
        ->name('calendar.month')
        ->where(['year' => '[0-9]{4}', 'month' => '(1[0-2]|0?[1-9])']);

    Route::get('/cards', [CardController::class, 'index'])->name('cards');
    Route::get('/cards/{card}', [CardController::class, 'show'])->name('cards.show');
    Route::put('/cards/{card}/note', [CardController::class, 'updateNote'])->name('cards.note.update');

    Route::get('/notes', [NoteController::class, 'index'])->name('notes');

    Route::inertia('/stats', 'Stats')->name('stats');
    Route::inertia('/themes', 'Themes')->name('themes');
    Route::get('/import', [ImportController::class, 'show'])->name('imports');
    Route::post('/import/review', [ImportController::class, 'review'])->name('imports.review');
    Route::post('/import', [ImportController::class, 'store'])->name('imports.store');

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/export/backup.json', [ExportController::class, 'json'])->name('export.json');
    Route::get('/export/days.csv', [ExportController::class, 'days'])->name('export.days');
    Route::get('/export/card-notes.csv', [ExportController::class, 'cardNotes'])->name('export.card-notes');
    Route::post('/restore', [RestoreController::class, 'store'])->name('restore.store');
});

require __DIR__.'/auth.php';
