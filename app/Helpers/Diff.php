<?php

namespace App\Helpers;

use GorHill\FineDiff\FineDiff;
use GorHill\FineDiff\FineDiffHTML;

abstract class Diff
{
    /**
     * Checks if given media type is diffable. If it is text then yes.
     *
     * @param string $mediaType
     * @return bool
     */
    public static function diffable(string $mediaType) {
        // some exceptions beyond text/abc media type
        if (in_array($mediaType, ['application/javascript'])) {
            return true;
        }

        $types = explode('/', $mediaType);

        return count($types) == 2 && $types[0] == 'text';
    }

    public static function renderChangesToHtml($from, $to) {
        /**
         * More info: https://github.com/BillyNate/PHP-FineDiff
        If you wish a different granularity from the default one, you can use
        one of the provided stock granularity stacks:

        FineDiff::$paragraphGranularity
        FineDiff::$sentenceGranularity
        FineDiff::$wordGranularity
        FineDiff::$characterGranularity (default)
         */
        $opCodes = FineDiff::getDiffOpcodes($from, $to, FineDiff::$wordGranularity);
        return FineDiffHTML::renderDiffToHTMLFromOpcodes($from, $opCodes);
    }
}