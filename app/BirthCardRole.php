<?php

namespace App;

enum BirthCardRole: string
{
    case BirthAtu = 'birth_atu';
    case DecanCard = 'decan_card';
    case SunSignAtu = 'sun_sign_atu';
    case CourtCard = 'court_card';

    /**
     * The role's name as shown on the card badge.
     */
    public function label(): string
    {
        return match ($this) {
            self::BirthAtu => 'Birth Atu',
            self::DecanCard => 'Decan card',
            self::SunSignAtu => 'Sun-sign Atu',
            self::CourtCard => 'Court card',
        };
    }
}
