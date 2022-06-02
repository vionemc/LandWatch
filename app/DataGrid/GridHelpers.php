<?php

declare(strict_types=1);

namespace App\DataGrid;

use JetBrains\PhpStorm\Pure;

use function array_filter;
use function array_key_exists;
use function count;

final class GridHelpers {

    #[Pure]
    public static function isSequentialArray(array $array): bool {
        return !self::isAssociativeArray($array);
    }

    public static function isAssociativeArray(array $array): bool {
        // An empty array is in theory a valid associative array
        // so we return 'true' for empty.
        if ([] === $array) {
            return true;
        }

        // Check if all indexed keys are present
        // return 'true' when we encounter first non-indexed key
        $n = count($array);
        for ($i = 0; $i < $n; $i++) {
            if (!array_key_exists($i, $array)) {
                return true;
            }
        }

        // Dealing with sequential array
        return false;
    }

    public static function isWithoutDot(string $value): bool {
        return !str_contains($value, '.');
    }

    public static function getArrayValuesWithoutDot(array $array): array
    {
        return array_filter($array, [__CLASS__, 'isWithoutDot']);
    }
}
