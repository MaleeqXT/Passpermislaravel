<?php

namespace App\Support;

use App\Models\Roles\Admin\Area\Zone;

class CandidateZoneResolver
{
    public const CREIL_ID = '9eeb1c0b-3a2a-41f6-8887-e17879995335';
    public const TOULOUSE_ID = '9ef0999c-3842-4de1-ade2-d88412135519';
    public const OTHERS_ID = 'a0100610-3580-4c9c-9e96-51ca76369464';

    public static function forVille(?string $ville): string
    {
        $zoneName = match (strtolower(trim((string) $ville))) {
            'creil' => 'creil',
            'toulouse' => 'toulouse',
            default => 'others',
        };

        $fallbackId = match ($zoneName) {
            'creil' => self::CREIL_ID,
            'toulouse' => self::TOULOUSE_ID,
            default => self::OTHERS_ID,
        };

        return Zone::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$zoneName])
            ->value('id') ?? $fallbackId;
    }
}
