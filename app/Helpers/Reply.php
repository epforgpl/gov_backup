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
    public static function detectEncoding(\DOMDocument $doc) {
        $xPath = new \DOMXPath($doc);
        /**
         * @var $nodeList \DOMNodeList
         */
        $nodeList = $xPath->query("head/meta/@charset");
        if ($nodeList->length) {
            return trim($nodeList->item(0)->textContent);
        }

        $nodeList = $xPath->query("head/meta[@http-equiv='content-type']/@content");
        if ($nodeList->length) {
            if (preg_match("/charset=(.+)/", $nodeList->item(0)->textContent, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public static function replyHtml($body, $rewrite_callback) {
        $d = new \DOMDocument();
        $d->loadHTML($body, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        $actualEncoding = self::detectEncoding($d);

        // Load HTML won't be able to guess encoding for websites who implement it wrong (not as a first tag / in the first 255 chars)
        // see http://php.net/manual/en/domdocument.loadhtml.php#78243 for explanation
        // we need to do a hack http://php.net/manual/en/domdocument.loadhtml.php#95251 to make sure it's read properly
        if ($actualEncoding !== null) {
            // TODO use regexps to detectEncoding instead of DOM not to rebuilt it
            // TODO test handling different encodings and meta tags so we don't rebuild it without a good reason
            $d->loadHTML("<?xml encoding=\"$actualEncoding\">" .$body, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
            foreach ($d->childNodes as $item)
                if ($item->nodeType == XML_PI_NODE)
                    $d->removeChild($item); // remove hack

            $d->encoding = $encoding = $actualEncoding;
        }

        $xPath = new \DOMXPath($d);

        // rewrite meta tags
        $meta_tags = $xPath->query("head//meta[@property='og:image']");
        foreach( $meta_tags as $meta ) {
            /**
             * @var $meta \DOMElement
             */
            if ($content = $meta->getAttribute('content')) {
                $meta->setAttribute('content', $rewrite_callback($content, 'get'));
            }
        }

        // rewrite external scripts
        $scripts = $xPath->query("//script");
        foreach ($scripts as $script) {
            if ($src = $script->getAttribute('src')) {
                if ($rewritten = $rewrite_callback($src, 'get')) {
                    $script->setAttribute('src', $rewritten);
                }
            }
        }

        $links = $xPath->query("//link");
        foreach( $links as $a ) {
            if( $href = $a->getAttribute('href') ) {
                if( $url = $rewrite_callback($href, 'get') ) {
                    $a->setAttribute('href', $url);
                }
            }
        }

        // rewrite all the links
        $anchors = $xPath->query("//a");
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
        $images = $xPath->query("//img");
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
        $inputs = $xPath->query("//input");
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
     * @param bool $returnParsed if not then string is build
     * @return array|string|null
     * @throws \Exception
     */
    public static function createAbsoluteStandardizedUrl(string $url, string $base_url, bool $returnParsed = false)
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

        if ($returnParsed) {
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
        $scheme = !empty($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = !empty($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = !empty($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = !empty($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = !empty($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = !empty($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = !empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = !empty($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}