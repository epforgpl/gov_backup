<?php

namespace App\Helpers;


abstract class Diff
{
    /**
     * Checks if given media type is diffable. If it is text then yes.
     *
     * @param string $mediaType
     * @return bool
     */
    public static function diffable(string $mediaType) {
        $types = explode('/', $mediaType);

        return count($types) == 2 && $types[0] == 'text';
    }
}