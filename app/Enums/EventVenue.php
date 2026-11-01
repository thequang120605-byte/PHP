<?php

namespace App\Enums;

enum EventVenue: string
{
    case Hall = 'Hội trường C';
    case Library = 'Tầng 3 thư viện';
    case Yard = 'Sân trường';
    case Conference = 'Phòng hội thảo';
    case Online = 'Online (Google Meet/MS Teams)';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
