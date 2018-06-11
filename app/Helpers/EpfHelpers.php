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

    /**
     * Handle Laravel cutting trailing slashes
     */
    public static function route_slashed($name, $parameters = [], $absolute = true) {
        if (!isset($parameters['url'])) {
            return route($name, $parameters, $absolute);
        }

        $url = $parameters['url'];
        $parameters['url'] = 'SAVE_TRAILING_SLASH';
        return str_replace('SAVE_TRAILING_SLASH', $url , route($name, $parameters, $absolute));
    }
}