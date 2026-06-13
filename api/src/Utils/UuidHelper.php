<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\Uid\Uuid;

final class UuidHelper
{
    public static function equals(?Uuid $a, ?Uuid $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return $a->equals($b);
    }
}
