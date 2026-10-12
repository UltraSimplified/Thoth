<?php

namespace App;

enum Suit: string
{
    case Wands = 'wands';
    case Cups = 'cups';
    case Swords = 'swords';
    case Disks = 'disks';

    /**
     * The suit's name as it appears on the card and in the UI.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * The Qabalistic world the suit belongs to, shown as a label on the card page.
     */
    public function world(): string
    {
        return match ($this) {
            self::Wands => 'Atziluth',
            self::Cups => 'Briah',
            self::Swords => 'Yetzirah',
            self::Disks => 'Assiah',
        };
    }

    /**
     * The element the suit represents. Court sub-elements are seeded separately.
     */
    public function element(): string
    {
        return match ($this) {
            self::Wands => 'fire',
            self::Cups => 'water',
            self::Swords => 'air',
            self::Disks => 'earth',
        };
    }
}
