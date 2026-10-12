<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Services\CardImageStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportCardImages extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cards:import-images
        {dir : Folder of scans, one per card, named by card, e.g. "The Aeon.jpg" or "seven-of-disks.png"}
        {--ratio=0.6 : Width to height of the cropped card}';

    /**
     * @var string
     */
    protected $description = 'Crop, resize and name scans of the cards as WebP files in storage/app/public/cards';

    /**
     * Scans GD can read. TIFF is not supported by GD.
     */
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function handle(CardImageStore $images): int
    {
        $directory = rtrim((string) $this->argument('dir'), '/');

        if (! is_dir($directory)) {
            $this->error("No folder at {$directory}.");

            return self::FAILURE;
        }

        $ratio = (float) $this->option('ratio');

        if ($ratio <= 0) {
            $this->error('The ratio must be greater than zero.');

            return self::FAILURE;
        }

        $imported = [];
        $unmatched = [];
        $unreadable = [];

        $scans = collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file): bool => in_array(strtolower($file->getExtension()), self::EXTENSIONS, true))
            ->sortBy(fn (\SplFileInfo $file): string => $file->getFilename());

        foreach ($scans as $scan) {
            $card = $this->cardFor($scan->getFilenameWithoutExtension());

            if ($card === null) {
                $unmatched[] = $scan->getFilename();

                continue;
            }

            if (! $images->storeScan($card, (string) file_get_contents($scan->getPathname()), $ratio)) {
                $unreadable[] = $scan->getFilename();

                continue;
            }

            $imported[] = $card->name;
        }

        $this->info('Imported '.count($imported).' card '.Str::plural('image', count($imported)).'.');

        if ($unmatched !== []) {
            $this->warn('No card matches: '.implode(', ', $unmatched));
        }

        if ($unreadable !== []) {
            $this->warn('Could not read: '.implode(', ', $unreadable));
        }

        return self::SUCCESS;
    }

    /**
     * Find the card a scan belongs to. Accepts the slug ("the-aeon"), the name ("The Aeon") or a
     * numbered prefix, as in "XX The Aeon" or "20-the-aeon".
     */
    private function cardFor(string $basename): ?Card
    {
        $slug = Str::slug($basename);

        return Card::query()->where('slug', $slug)->first()
            ?? Card::query()->where('slug', preg_replace('/^(?:[ivxlc]+|\d+)-/', '', $slug))->first();
    }
}
