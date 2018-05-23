<?php
/**
 * Created by PhpStorm.
 * User: kmadejski
 * Date: 22.05.18
 * Time: 19:32
 */

namespace App\Helpers;


abstract class Reply
{
    public static function replyHtml($body, $rewrite_callback) {
        $d = new \DOMDocument();
        $d->loadHTML($body, LIBXML_HTML_NODEFDTD);

        // rewrite meta tags
        $meta_tags = (new \DOMXPath($d))->query("head//meta[@property='og:image']");
        foreach( $meta_tags as $meta ) {
            /**
             * @var $meta \DOMElement
             */
            if ($content = $meta->getAttribute('content')) {
                $meta->setAttribute('content', $rewrite_callback($content, 'get'));
            }
        }

        // rewrite external scripts
        $scripts = (new \DOMXPath($d))->query("//script");
        foreach ($scripts as $script) {
            if ($src = $script->getAttribute('src')) {
                if ($rewritten = $rewrite_callback($src, 'get')) {
                    $script->setAttribute('src', $rewritten);
                }
            }
        }

        $links = (new \DOMXPath($d))->query("//link");
        foreach( $links as $a ) {
            if( $href = $a->getAttribute('href') ) {
                if( $url = $rewrite_callback($href, 'get') ) {
                    $a->setAttribute('href', $url);
                }
            }
        }

        // rewrite all the links
        $anchors = (new \DOMXPath($d))->query("//a");
        foreach( $anchors as $a ) {
            /**
             * @var $a \DOMElement
             */
            if ($url = $a->getAttribute('href')) {
                // TODO this will be handled by createAbsoluteUrl
                // don't rewrite urls that don't point to resources
                foreach (['file', 'mailto', 'data', 'javascript'] as $prefix) {
                    if (stripos($url, $prefix . ':') === 0) {
                        continue 2;
                    }
                }

                if (stripos($url, '#') === 0) {
                    // don't rewrite internal fragments
                    continue;
                }

                // rewrite as an archived link to our website (type = 'web')
                if ($rewritten = $rewrite_callback($url, 'web')) {
                    $a->setAttribute('href', $rewritten);
                    $a->setAttribute('target', '_top');

                } else {
                    // url out of archiving scope
                    // if it's not a fragment within page then make a link to open in a new page
                    // TODO test how fragments behave out and in of the page
                    $a->setAttribute('target', '_blank');
                }
            }
        }

        // rewrite images
        $images = (new \DOMXPath($d))->query("//img");
        foreach( $images as $a ) {
            if( $src = $a->getAttribute('src') ) {
                if( $rewritten = $rewrite_callback($src, 'get') ) {
                    $a->setAttribute('src', $rewritten);
                }
            }
            if( $url = $a->getAttribute('data-original') ) {
                if( $rewritten = $rewrite_callback($url, 'get') ) {
                    $a->setAttribute('data-original', $rewritten);
                }
            }
        }

        // rewrite input
        $inputs = (new \DOMXPath($d))->query("//input");
        foreach( $inputs as $a ) {
            if( $src = $a->getAttribute('src') ) {
                if( $rewritten = $rewrite_callback($src, 'get') ) {
                    $a->setAttribute('src', $rewritten);
                }
            }
        }

        return $d->saveHTML();
    }

    public static function replyCss($body, callable $rewrite_callback) {
        $body = preg_replace_callback('/url\(\s*(.*?)\s*\)/i', function($url_match) use($rewrite_callback) {
            // get the value inside url()
            $url = $url_match[1];
            $qt = '';

            // escape single quotes
            if( preg_match('/^\'(.*?)\'$/', $url, $match) ) {
                $url = $match[1];
                $qt = '\'';

            } else if( preg_match('/^\"(.*?)\"$/', $url, $match) ) {
                // escape double quotes
                $url = $match[1];
                $qt = '"';
            }

            if( $rewritten = $rewrite_callback($url, 'get') ) {
                return 'url(' . $qt . $rewritten . $qt . ')';

            } else {
                return $url_match[0];
            }
        }, $body);

        return $body;
    }

    /**
     * Merge url with an absolute base and standardize. Return null if it shouldn't be rewritten.
     *
     * @param string $url
     * @param string $base_url
     * @param bool $returnArray if not then string is build
     * @return array|string|null
     * @throws \Exception
     */
    public static function createAbsoluteStandardizedUrl(string $url, string $base_url, bool $returnArray = false)
    {
        // Validate $url
        $url = trim($url);
        $base_url = trim($base_url);
        if (empty($url) or empty($base_url)) {
            throw new \InvalidArgumentException('Urls cannot be empty.');
        }

        // Skip local fragments
        if (strpos($url, '#') === 0) {
            return null;
        }

        // Skip urls that don't point to resources
        foreach (['fail', 'mailto', 'data', 'javascript'] as $prefix) {
            if (stripos($url, $prefix . ':') === 0) {
                return null;
            }
        }

        if (!$parsed = parse_url($url)) {
            return null;
        }
        if (empty($parsed['path'])) {
            $parsed['path'] = '';
        }

        if (empty($parsed['scheme']) || empty($parsed['host'])) {
            if (!$base_url) {
                throw new \Exception("Url is relative and there is no base url to resolve it");
            }

            if(!$base_parsed = parse_url($base_url)) {
                throw new \Exception("Malformed url: $base_url");
            }

            if (empty($base_parsed['path'])) {
                $base_parsed['path'] = '';
            }

            // fill in scheme and host if missing
            foreach (['scheme', 'host'] as $field) {
                if (empty($parsed[$field])) {
                    $parsed[$field] = $base_parsed[$field];
                }
                if (empty($parsed[$field])) {
                    throw new \Exception("Base url is missing $field");
                }
            }

            // resolve absolute and relative paths
            if ($parsed['path']) {
                if ($parsed['path'][0] === '/') {
                    // This is an absolute path. Leave it as it is.
                } else {
                    // This is a path that is relative to $base_url. We need to resolve its absolute path

                    $base_url_path_parts = explode('/', $base_parsed['path']);
                    if (!empty($base_url_path_parts)) {
                        // pop file to get directory
                        array_pop($base_url_path_parts);
                    }

                    array_push($base_url_path_parts, $parsed['path']);

                    $parsed['path'] = '/' . implode('/', $base_url_path_parts);
                }
            } else {
                // keep the path of the base url
                $parsed['path'] = $base_parsed['path'];
            }
        }

        // clear duplicated slashes
        $parsed['path'] = preg_replace('/\/+/', '/', $parsed['path']);

        // clear contracted url, such as ../
        $path = explode('/', $parsed['path']);

        for ($i = 0; $i < count($path);) {
            if ($path[$i] == '..') {
                if ($i == 0) {
                    throw new \InvalidArgumentException("Url contains invalid contracting '../' element.");
                }
                array_splice($path, $i - 1, 2);

                $i--;
            } else {
                $i++;
            }
        }

        $parsed['path'] = implode('/', $path);

        // Standardize url
        // Standardize: lowercase host
        $parsed['host'] = strtolower($parsed['host']);

        // Standardize: sort query params
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
            ksort($query);
            $parsed['query'] = http_build_query($query);
        }

        if ($returnArray) {
            $array = [];
            foreach (['scheme', 'host', 'port', 'user', 'pass', 'path', 'query'] as $field) {
                $array[$field] = $parsed[$field] ?? '';
            }

            return $array;
        }

        return self::unparse_url($parsed);
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