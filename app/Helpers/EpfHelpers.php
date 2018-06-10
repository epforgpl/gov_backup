<?php

namespace App\Helpers;

class EpfHelpers {
    public static function array_any(array $array, callable $fn)
    {
        foreach ($array as $value) {
            if ($fn($value)) {
                return true;
            }
        }
        return false;
    }
}