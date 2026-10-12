<?php

namespace App\Console\Commands;

use App\Models\Card;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class VerifyCardLinks extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cards:verify-links';

    /**
     * @var string
     */
    protected $description = 'Check that each card\'s thothreadings.com page still resolves';

    /**
     * Some servers refuse HEAD requests, so these statuses are retried with GET.
     */
    private const RETRY_WITH_GET = [403, 405, 501];

    public function handle(): int
    {
        $cards = Card::query()->orderBy('slug')->get(['name', 'thothreadings_url']);

        $broken = [];

        foreach ($cards as $card) {
            $status = $this->statusOf($card->thothreadings_url);

            if ($status === null || $status >= 400) {
                $broken[] = [$card->name, $card->thothreadings_url, $status ?? 'no response'];
            }
        }

        if ($broken === []) {
            $this->info("All {$cards->count()} card links resolve.");

            return self::SUCCESS;
        }

        $this->table(['Card', 'URL', 'Status'], $broken);
        $this->error(count($broken).' of '.$cards->count().' card links did not resolve.');

        return self::FAILURE;
    }

    /**
     * The HTTP status a URL answers with, or null when it cannot be reached.
     */
    private function statusOf(string $url): ?int
    {
        try {
            $response = $this->request()->head($url);

            if (in_array($response->status(), self::RETRY_WITH_GET, true)) {
                $response = $this->request()->get($url);
            }

            return $response->status();
        } catch (ConnectionException) {
            return null;
        }
    }

    private function request(): PendingRequest
    {
        return Http::withUserAgent('Thoth link check (personal card log)')->timeout(15);
    }
}
