<?php

namespace App;

enum Sephira: string
{
    case Kether = 'kether';
    case Chokmah = 'chokmah';
    case Binah = 'binah';
    case Chesed = 'chesed';
    case Geburah = 'geburah';
    case Tiphareth = 'tiphareth';
    case Netzach = 'netzach';
    case Hod = 'hod';
    case Yesod = 'yesod';
    case Malkuth = 'malkuth';

    /**
     * The sephira's position on the Tree, from 1 (Kether) to 10 (Malkuth).
     */
    public function number(): int
    {
        return match ($this) {
            self::Kether => 1,
            self::Chokmah => 2,
            self::Binah => 3,
            self::Chesed => 4,
            self::Geburah => 5,
            self::Tiphareth => 6,
            self::Netzach => 7,
            self::Hod => 8,
            self::Yesod => 9,
            self::Malkuth => 10,
        };
    }

    /**
     * The pillar the sephira stands on: Mercy, Severity or Middle.
     */
    public function pillar(): string
    {
        return match ($this) {
            self::Chokmah, self::Chesed, self::Netzach => 'Mercy',
            self::Binah, self::Geburah, self::Hod => 'Severity',
            self::Kether, self::Tiphareth, self::Yesod, self::Malkuth => 'Middle',
        };
    }

    /**
     * The triad the sephira belongs to: Supernal, Ethical, Astral or Malkuth.
     */
    public function triad(): string
    {
        return match ($this) {
            self::Kether, self::Chokmah, self::Binah => 'Supernal',
            self::Chesed, self::Geburah, self::Tiphareth => 'Ethical',
            self::Netzach, self::Hod, self::Yesod => 'Astral',
            self::Malkuth => 'Malkuth',
        };
    }

    /**
     * The sephira's name as shown in the UI.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
