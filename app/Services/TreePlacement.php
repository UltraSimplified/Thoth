<?php

namespace App\Services;

use App\Models\Card;
use App\Sephira;
use App\TreePlacementStyle;
use Illuminate\Support\Collection;

/**
 * Places cards on the Qabalistic Tree of Life. Each card's Crowley position is seeded; the Golden Dawn
 * placement swaps the Emperor and the Star at read time, so the seed never changes.
 */
final class TreePlacement
{
    /**
     * The 22 paths of the Tree, keyed by path number, with each path's Hebrew letter and the sephiroth it joins.
     *
     * @var array<int, array{letter: string, from: Sephira, to: Sephira}>
     */
    public const PATHS = [
        11 => ['letter' => 'Aleph', 'from' => Sephira::Kether, 'to' => Sephira::Chokmah],
        12 => ['letter' => 'Beth', 'from' => Sephira::Kether, 'to' => Sephira::Binah],
        13 => ['letter' => 'Gimel', 'from' => Sephira::Kether, 'to' => Sephira::Tiphareth],
        14 => ['letter' => 'Daleth', 'from' => Sephira::Chokmah, 'to' => Sephira::Binah],
        15 => ['letter' => 'Heh', 'from' => Sephira::Chokmah, 'to' => Sephira::Tiphareth],
        16 => ['letter' => 'Vav', 'from' => Sephira::Chokmah, 'to' => Sephira::Chesed],
        17 => ['letter' => 'Zain', 'from' => Sephira::Binah, 'to' => Sephira::Tiphareth],
        18 => ['letter' => 'Cheth', 'from' => Sephira::Binah, 'to' => Sephira::Geburah],
        19 => ['letter' => 'Teth', 'from' => Sephira::Chesed, 'to' => Sephira::Geburah],
        20 => ['letter' => 'Yod', 'from' => Sephira::Chesed, 'to' => Sephira::Tiphareth],
        21 => ['letter' => 'Kaph', 'from' => Sephira::Chesed, 'to' => Sephira::Netzach],
        22 => ['letter' => 'Lamed', 'from' => Sephira::Geburah, 'to' => Sephira::Tiphareth],
        23 => ['letter' => 'Mem', 'from' => Sephira::Geburah, 'to' => Sephira::Hod],
        24 => ['letter' => 'Nun', 'from' => Sephira::Tiphareth, 'to' => Sephira::Netzach],
        25 => ['letter' => 'Samekh', 'from' => Sephira::Tiphareth, 'to' => Sephira::Yesod],
        26 => ['letter' => 'Ayin', 'from' => Sephira::Tiphareth, 'to' => Sephira::Hod],
        27 => ['letter' => 'Peh', 'from' => Sephira::Netzach, 'to' => Sephira::Hod],
        28 => ['letter' => 'Tzaddi', 'from' => Sephira::Netzach, 'to' => Sephira::Yesod],
        29 => ['letter' => 'Qoph', 'from' => Sephira::Netzach, 'to' => Sephira::Malkuth],
        30 => ['letter' => 'Resh', 'from' => Sephira::Hod, 'to' => Sephira::Yesod],
        31 => ['letter' => 'Shin', 'from' => Sephira::Hod, 'to' => Sephira::Malkuth],
        32 => ['letter' => 'Tav', 'from' => Sephira::Yesod, 'to' => Sephira::Malkuth],
    ];

    public function __construct(
        private readonly TreePlacementStyle $style = TreePlacementStyle::Crowley,
    ) {}

    /**
     * The path or sephira a card occupies under the current placement style.
     *
     * @return array{path: ?int, sephira: ?Sephira}
     */
    public function positionFor(Card $card): array
    {
        return [
            'path' => $this->pathFor($card),
            'sephira' => $card->sephira,
        ];
    }

    /**
     * The nodes and paths lit by a set of cards, ready for the TreeOfLife component.
     *
     * @param  iterable<Card>  $cards
     * @return array{nodes: list<string>, paths: list<int>}
     */
    public function highlightsFor(iterable $cards): array
    {
        $nodes = [];
        $paths = [];

        foreach ($cards as $card) {
            $position = $this->positionFor($card);

            if ($position['sephira'] !== null) {
                $nodes[$position['sephira']->value] = $position['sephira']->value;
            }

            if ($position['path'] !== null) {
                $paths[$position['path']] = $position['path'];
            }
        }

        return [
            'nodes' => array_values($nodes),
            'paths' => array_values($paths),
        ];
    }

    /**
     * Every node and path with the cards that sit there, for the full Tree on the card page.
     *
     * @param  Collection<int, Card>  $cards
     * @return array{nodes: array<string, list<array<string, mixed>>>, paths: array<int, list<array<string, mixed>>>}
     */
    public function cardsByPosition(Collection $cards): array
    {
        $nodes = collect(Sephira::cases())
            ->mapWithKeys(fn (Sephira $sephira): array => [$sephira->value => []])
            ->all();
        $paths = collect(array_keys(self::PATHS))
            ->mapWithKeys(fn (int $number): array => [$number => []])
            ->all();

        foreach ($cards as $card) {
            $position = $this->positionFor($card);

            if ($position['sephira'] !== null) {
                $nodes[$position['sephira']->value][] = $this->summary($card);
            }

            if ($position['path'] !== null) {
                $paths[$position['path']][] = $this->summary($card);
            }
        }

        return ['nodes' => $nodes, 'paths' => $paths];
    }

    /**
     * Where a card sits, described for the text key beside the Tree: its role, a label, its name, and its sephira or path.
     *
     * @return array{role: string, label: string, card: string, sephira: ?string, path: ?int}
     */
    public function elementFor(Card $card, string $role, string $label): array
    {
        $position = $this->positionFor($card);

        return [
            'role' => $role,
            'label' => $label,
            'card' => $card->name,
            'sephira' => $position['sephira']?->value,
            'path' => $position['path'],
        ];
    }

    private function pathFor(Card $card): ?int
    {
        if ($this->style === TreePlacementStyle::GoldenDawn) {
            return match ($card->slug) {
                'the-emperor' => 15,
                'the-star' => 28,
                default => $card->tree_path,
            };
        }

        return $card->tree_path;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Card $card): array
    {
        return [
            'slug' => $card->slug,
            'name' => $card->name,
            'title' => $card->title,
        ];
    }
}
