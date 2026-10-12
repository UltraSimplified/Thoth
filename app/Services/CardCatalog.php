<?php

namespace App\Services;

use App\BirthCardRole;
use App\Http\Resources\CardResource;
use App\Models\Card;
use Illuminate\Support\Collection;

/**
 * The cards as the pages receive them: the resource shape, with the owner's birth-card roles attached.
 */
final class CardCatalog
{
    public function __construct(
        private readonly AppSettings $settings,
        private readonly BirthCards $birthCards,
    ) {}

    /**
     * Every card in picker order: Majors, then Wands, Cups, Swords and Disks, each by number.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $birthRoles = $this->birthRoles();

        return Card::query()
            ->orderByRaw("CASE suit WHEN 'wands' THEN 1 WHEN 'cups' THEN 2 WHEN 'swords' THEN 3 WHEN 'disks' THEN 4 ELSE 0 END")
            ->orderBy('number')
            ->get()
            ->map(fn (Card $card): array => $this->present($card, $birthRoles))
            ->values()
            ->all();
    }

    /**
     * One card, with the owner's birth-card roles attached.
     *
     * @param  array<int, array<int, string>>|null  $birthRoles
     * @return array<string, mixed>
     */
    public function present(Card $card, ?array $birthRoles = null): array
    {
        return (new CardResource($card))->resolve() + [
            'birthRoles' => ($birthRoles ?? $this->birthRoles())[$card->id] ?? [],
        ];
    }

    /**
     * The six birth cards for the owner's birth date, each with its role. Empty until a birth date is set.
     *
     * @return list<array{role: BirthCardRole, card: Card}>
     */
    public function birthEntries(): array
    {
        $birthDate = $this->settings->birthDate();

        return $birthDate === null ? [] : $this->birthCards->forBirthDate($birthDate);
    }

    /**
     * Which birth-card roles each card holds, keyed by card id.
     *
     * @return array<int, array<int, string>>
     */
    public function birthRoles(): array
    {
        return collect($this->birthEntries())
            ->groupBy(fn (array $entry): int => $entry['card']->id)
            ->map(fn (Collection $entries): array => $entries
                ->map(fn (array $entry): string => $entry['role']->label())
                ->values()
                ->all())
            ->all();
    }

    /**
     * The six birth positions, each named by its role, for the text key beside the Tree.
     *
     * @return list<array{role: string, label: string, card: string, sephira: ?string, path: ?int}>
     */
    public function birthElements(TreePlacement $placement): array
    {
        return array_map(
            fn (array $entry): array => $placement->elementFor($entry['card'], 'birth', 'Birth: '.$entry['role']->treeLabel()),
            $this->birthEntries(),
        );
    }
}
