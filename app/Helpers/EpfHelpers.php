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
     * Contracts ../ and ./ in path parts (in place)
     *
     * TODO test it
     * @param array $parts
     * @throws \InvalidArgumentException if path starts with ../
     * @return void
     */
    public static function contract_path_parts(array &$parts)
    {
        // contract ./
        while(($idx = array_search('.', $parts)) !== false) {
            array_splice($parts, $idx, 1);
        }

        // contract ../
        while(($idx = array_search('..', $parts)) !== false) {
            if ($idx == 0) {
                throw new \InvalidArgumentException("../ cannot be at the start of the path");
            }
            array_splice($parts, $idx - 1, 2);
        }
    }

    public static function unparse_url($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}