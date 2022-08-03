<?php

namespace App\Enum;

enum LegalGuardianRole: string
{
    case Mother = 'mother';
    case Father = 'father';
    case Guardian = 'guardian';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mother => 'Mère',
            self::Father => 'Père',
            self::Guardian => 'Tuteur/Tutrice',
            self::Other => 'Autre',
        };
    }
}
