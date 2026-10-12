<?php

namespace App\Http\Controllers;

use App\Services\BackupExporter;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    /**
     * The full backup as JSON. Restore it from the settings page.
     */
    public function json(BackupExporter $exporter): Response
    {
        return $this->download(
            json_encode($exporter->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'application/json',
            'thoth-backup',
            'json',
        );
    }

    /**
     * Days as CSV, one row per day.
     */
    public function days(BackupExporter $exporter): Response
    {
        return $this->download($exporter->daysCsv(), 'text/csv; charset=UTF-8', 'thoth-days', 'csv');
    }

    /**
     * Card notes as CSV.
     */
    public function cardNotes(BackupExporter $exporter): Response
    {
        return $this->download($exporter->cardNotesCsv(), 'text/csv; charset=UTF-8', 'thoth-card-notes', 'csv');
    }

    private function download(string $body, string $contentType, string $name, string $extension): Response
    {
        return response($body, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$name.'-'.today()->toDateString().'.'.$extension.'"',
        ]);
    }
}
