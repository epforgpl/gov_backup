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

    /**
     * Strip lines that start with //
     */
    public static function strip_json_comments(string $text) {
        $result = [];
        foreach(mb_split("\n", $text) as $line) {
            if (!starts_with(trim($line), '//')) {
                array_push($result, $line);
            }
        }
        return join("\n", $result);
    }
}