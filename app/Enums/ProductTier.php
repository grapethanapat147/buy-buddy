<?php

namespace App\Enums;

enum ProductTier: string
{
    case Must = 'must';
    case Recommended = 'recommended';
    case Optional = 'optional';

    public function priority(): int
    {
        return match ($this) {
            self::Must => 0,
            self::Recommended => 1,
            self::Optional => 2,
        };
    }
}
