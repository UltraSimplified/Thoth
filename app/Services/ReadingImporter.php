<?php

namespace App\Services;

use App\Models\Card;

/**
 * Reads the paste-in reading list (spec 4.9) into one row per day. Nothing is saved here: the review screen
 * shows every row with its problems, and only confirmed rows are written.
 */
final class ReadingImporter
{
    private const MONTH_HEADER = '/^(?:january|february|march|april|may|june|july|august|september|october|november|december)\b/i';

    private const NUMERIC_DATE = '/(?<![\d\/])(\d{1,2})\/(\d{1,2})(?![\d\/])/';

    private const WORD_DATE = '/(?<![\w])(\d{1,2})(?:st|nd|rd|th)?\s+(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\b/i';

    private const MONTH_FIRST_DATE = '/(?<![\w])(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\.?\s+(\d{1,2})(?:st|nd|rd|th)?(?![\w])/i';

    private const MONTHS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    public function __construct(
        private readonly CardReferenceResolver $cards,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $text, int $year): array
    {
        $rows = [];
        $currentIndex = null;
        $currentIndent = -1;

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $index => $line) {
            $content = $this->contentOf($line);

            if ($content === '') {
                continue;
            }

            $indent = $this->indentOf($line);

            if ($indent === 0 && preg_match(self::MONTH_HEADER, $content) === 1) {
                $currentIndex = null;

                continue;
            }

            if ($currentIndex !== null && $indent > $currentIndent && ! $this->hasDate($content)) {
                $this->addClarifiers($rows[$currentIndex], $content, $index + 1);

                continue;
            }

            $rows[] = $this->parseEntry($content, trim($line), $index + 1, $year);
            $currentIndex = array_key_last($rows);
            $currentIndent = $indent;
        }

        return $this->flagDuplicateDates($rows);
    }

    /**
     * @return array{line: int, raw: string, card_text: ?string, date: ?string, card_id: ?int, clarifier_ids: list<int>, notes: ?string, event_note: ?string, problems: list<string>}
     */
    private function parseEntry(string $content, string $raw, int $lineNumber, int $year): array
    {
        $notes = [];
        $content = $this->extractBrackets($content, $notes);
        $date = $this->extractDate($content, $year);
        $content = trim($date['remaining'], " \t—–-/,");

        $problems = [];

        if ($date['invalid']) {
            $problems[] = 'That date does not exist in '.$year.'.';
        }

        $candidates = preg_split('/\s*\/\s*/', $content) ?: [];
        $main = $this->splitParts($candidates[0] ?? '');
        $card = $main['card'] === '' ? null : $this->cards->resolve($main['card']);

        $notes = array_merge($notes, $main['extras']);

        if ($card !== null && $main['title'] !== null) {
            if (($card->title === null && ! $card->isMajor()) || $this->isNote($main['title'])) {
                $notes[] = $main['title'];
            } elseif (! $this->cards->titleMatches($card, $main['title'])) {
                $problems[] = $this->titleProblem($card, $main['title']);
            }
        }

        $clarifierIds = [];

        foreach (array_slice($candidates, 1) as $candidate) {
            $parts = $this->splitParts($candidate);
            $clarifier = $parts['card'] === '' ? null : $this->cards->resolve($parts['card']);

            if ($clarifier === null) {
                $problems[] = 'Clarifier not recognised: “'.$candidate.'”.';

                continue;
            }

            $clarifierIds[] = $clarifier->id;
            $notes = array_merge($notes, $parts['extras']);
        }

        $eventNote = null;

        if ($card !== null && $date['date'] === null) {
            $problems[] = 'Card with no date.';
        }

        if ($card === null && $date['date'] !== null) {
            $problems[] = 'Date with no card: saved as a note-only day if confirmed.';
            $eventNote = trim($content) === '' ? null : mb_substr(trim($content), 0, 255);
        }

        if ($card === null && $date['date'] === null) {
            $problems[] = 'Unrecognised line.';
        }

        return [
            'line' => $lineNumber,
            'raw' => $raw,
            'card_text' => $main['card'] === '' ? null : $main['card'],
            'date' => $date['date'],
            'card_id' => $card?->id,
            'clarifier_ids' => array_values(array_unique($clarifierIds)),
            'notes' => $notes === [] ? null : implode('; ', array_filter($notes, fn (string $note): bool => $note !== '')),
            'event_note' => $eventNote,
            'problems' => $problems,
        ];
    }

    /**
     * An indented card line beneath a day is one of that day's clarifiers.
     *
     * @param  array<string, mixed>  $row
     */
    private function addClarifiers(array &$row, string $content, int $lineNumber): void
    {
        $notes = [];
        $content = $this->extractBrackets($content, $notes);

        foreach (preg_split('/\s*\/\s*/', $content) ?: [] as $candidate) {
            $parts = $this->splitParts($candidate);
            $card = $parts['card'] === '' ? null : $this->cards->resolve($parts['card']);

            if ($card === null) {
                $row['problems'][] = 'Clarifier on line '.$lineNumber.' not recognised: “'.trim($candidate).'”.';

                continue;
            }

            if (! in_array($card->id, $row['clarifier_ids'], true)) {
                $row['clarifier_ids'][] = $card->id;
            }

            $notes = array_merge($notes, $parts['extras']);
        }

        if ($notes !== []) {
            $row['notes'] = trim(implode('; ', array_filter([$row['notes'], ...$notes])), '; ');
        }
    }

    /**
     * Pull bracketed text out of a line into the notes. Brackets never hold a card or a date.
     *
     * @param  list<string>  $notes
     */
    private function extractBrackets(string $content, array &$notes): string
    {
        $stripped = preg_replace_callback(
            '/[\(\[]([^\)\]]*)[\)\]]/',
            function (array $match) use (&$notes): string {
                $notes[] = trim($match[1]);

                return ' ';
            },
            $content,
        );

        return trim($stripped ?? $content);
    }

    /**
     * @return array{date: ?string, invalid: bool, remaining: string}
     */
    private function extractDate(string $content, int $year): array
    {
        if (preg_match(self::NUMERIC_DATE, $content, $match, PREG_OFFSET_CAPTURE) === 1) {
            return $this->dateFrom((int) $match[1][0], (int) $match[2][0], $year, $content, $match[0]);
        }

        if (preg_match(self::WORD_DATE, $content, $match, PREG_OFFSET_CAPTURE) === 1) {
            $month = self::MONTHS[strtolower(substr($match[2][0], 0, 3))];

            return $this->dateFrom((int) $match[1][0], $month, $year, $content, $match[0]);
        }

        if (preg_match(self::MONTH_FIRST_DATE, $content, $match, PREG_OFFSET_CAPTURE) === 1) {
            $month = self::MONTHS[strtolower(substr($match[1][0], 0, 3))];

            return $this->dateFrom((int) $match[2][0], $month, $year, $content, $match[0]);
        }

        return ['date' => null, 'invalid' => false, 'remaining' => $content];
    }

    /**
     * @param  array{0: string, 1: int}  $matched
     * @return array{date: ?string, invalid: bool, remaining: string}
     */
    private function dateFrom(int $day, int $month, int $year, string $content, array $matched): array
    {
        $remaining = trim(substr_replace($content, ' ', $matched[1], strlen($matched[0])));

        if (! checkdate($month, $day, $year)) {
            return ['date' => null, 'invalid' => true, 'remaining' => $remaining];
        }

        return [
            'date' => sprintf('%04d-%02d-%02d', $year, $month, $day),
            'invalid' => false,
            'remaining' => $remaining,
        ];
    }

    /**
     * Text with an equals sign or a number is a note ("0 = 2"), never a card title.
     */
    private function isNote(string $text): bool
    {
        return str_contains($text, '=') || preg_match('/\d/', $text) === 1;
    }

    private function hasDate(string $content): bool
    {
        return preg_match(self::NUMERIC_DATE, $content) === 1
            || preg_match(self::WORD_DATE, $content) === 1
            || preg_match(self::MONTH_FIRST_DATE, $content) === 1;
    }

    /**
     * Split "XVI — The Tower" into the card, its title and any further text.
     *
     * @return array{card: string, title: ?string, extras: list<string>}
     */
    private function splitParts(string $candidate): array
    {
        $parts = array_values(array_filter(
            array_map('trim', preg_split('/\s+[—–-]\s+|\s*[—–]\s*/u', trim($candidate)) ?: []),
            fn (string $part): bool => $part !== '',
        ));

        return [
            'card' => $parts[0] ?? '',
            'title' => $parts[1] ?? null,
            'extras' => array_slice($parts, 2),
        ];
    }

    private function titleProblem(Card $card, string $title): string
    {
        if ($card->isMajor()) {
            $named = $this->cards->majorNamed($title);

            if ($named !== null && $named->id !== $card->id) {
                return 'Atu number and name disagree: '.$card->romanNumeral().' is '.$card->name.', not '.$named->name.'.';
            }
        }

        return 'Title doesn’t match the card: “'.$title.'” is not “'.($card->title ?? $card->name).'”.';
    }

    /**
     * Mark a second row for a date already used earlier in the list.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function flagDuplicateDates(array $rows): array
    {
        $firstLineByDate = [];

        foreach ($rows as $index => $row) {
            if ($row['date'] === null) {
                continue;
            }

            if (isset($firstLineByDate[$row['date']])) {
                $rows[$index]['problems'][] = 'Same date as line '.$firstLineByDate[$row['date']].'.';

                continue;
            }

            $firstLineByDate[$row['date']] = $row['line'];
        }

        return $rows;
    }

    private function contentOf(string $line): string
    {
        return trim(preg_replace('/^\s*[*•+\-]+\s*/u', '', $line) ?? '');
    }

    private function indentOf(string $line): int
    {
        $width = 0;

        foreach (str_split($line) as $character) {
            if ($character === ' ') {
                $width++;
            } elseif ($character === "\t") {
                $width += 4;
            } else {
                break;
            }
        }

        return $width;
    }
}
