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
    public const EMPTY_PATH = '/';

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

    /**
     * @param $body
     * @param string $page_url Replied page URL
     * @param callable $route_govbackup Rewrites absolute page url into govbackup link
     * @return string
     */
    public static function replyHtml($body, string $page_url, callable $route_govbackup) {
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

        $page_url_parsed = parse_url($page_url);
        $page_url_parsed['host'] = strtolower($page_url_parsed['host']);

        // set $base_url, overriding with <base> tag if present
        /**
         * @var \DOMNodeList
         */
        $base_tags = $xPath->query("head/base");
        $base_url = $page_url;

        if ($base_tags->length) {
            $base_tag = $base_tags->item(0);

            if ($base_href = $base_tag->getAttribute('href')) {
                $base_href_parsed = parse_url($base_href);

                $base_parsed = parse_url($base_url);

                // overwrite path
                $base_parsed['path'] = $base_href_parsed['path'] ?? ($base_parsed['path'] ?? '');

                // overwrite host & scheme if $base_href is relative
                $base_parsed['host'] = strtolower($base_href_parsed['host']) ?? $base_parsed['host'];
                $base_parsed['scheme'] = $base_href_parsed['scheme'] ?? $base_parsed['scheme'];

                $base_url = Reply::unparse_url($base_parsed);
            }
            $base_tag->parentNode->removeChild($base_tag);
        }
        // end detect <base> tag


        // do a pre- and post-processing of the url
        $rewrite_callback = function($url, $type) use ($route_govbackup, $base_url, $page_url_parsed) {
            $parsed = Reply::createAbsoluteStandardizedUrl($url, $base_url, true);

            if ($parsed == null) {
                // external => don't rewrite it
                return null;
            }

            // Skip local fragments, such as '#frag' or '/path#frag' or even those with hosts
            if (!empty($parsed['fragment'])
                and $parsed['host'] == $page_url_parsed['host']
                and $parsed['path'] == (isset($page_url_parsed['path']) ? $page_url_parsed['path'] : '/')
            ) {
                return '#' . $parsed['fragment'];
            }

            return $route_govbackup($parsed, $type);
        };

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
                // don't rewrite urls that don't point to resources
                foreach (['file', 'mailto', 'data', 'javascript'] as $prefix) {
                    if (stripos($url, $prefix . ':') === 0) {
                        continue 2;
                    }
                }

                // rewrite as an archived link to our website (type = 'web')
                if ($rewritten = $rewrite_callback($url, 'web')) {
                    $a->setAttribute('href', $rewritten);

                    if (stripos($rewritten, '#') !== 0) {
                        // don't retarget internal fragments, other links yes
                        $a->setAttribute('target', '_top');
                    }

                } else {
                    // url out of archiving scope
                    // if it's not a fragment within page then make a link to open in a new page
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

    /**
     * @param $body
     * @param string $base_url Replied resource URL
     * @param callable $route_govbackup function(array $parsed_url, string $type) Rewrites absolute page url into govbackup link
     * @return string
     */
    public static function replyCss($body, string $base_url, callable $route_govbackup) {
        // do a pre- and post-processing of the url
        $rewrite_callback = function($url, $type) use ($route_govbackup, $base_url) {
            $parsed = Reply::createAbsoluteStandardizedUrl($url, $base_url, true);

            // if external => don't rewrite it
            if ($parsed == null) return null;

            return $route_govbackup($parsed, $type);
        };

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

        // Skip urls that don't point to resources
        foreach (['file', 'mailto', 'data', 'javascript'] as $prefix) {
            if (stripos($url, $prefix . ':') === 0) {
                return null;
            }
        }

        if (!$parsed = parse_url($url)) {
            return null;
        }
        if (empty($parsed['path'])) {
            $parsed['path'] = ''; // '' = not set
        }

        if(!$base_parsed = parse_url($base_url)) {
            throw new \Exception("Malformed url: $base_url");
        }
        if (empty($base_parsed['path'])) {
            $base_parsed['path'] = '';
        }

        // Fill in scheme and host if missing
        if (empty($parsed['scheme']) || empty($parsed['host'])) {
            foreach (['scheme', 'host'] as $field) {
                if (empty($parsed[$field])) {
                    if (!isset($base_parsed[$field])) throw new \Exception("Base URL should have '$field' set.");
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

        // clear single dot
        $parsed['path'] = str_replace('/./', '/', $parsed['path']);

        // clear contracted url, such as ../
        $path = explode('/', $parsed['path']);

        for ($i = 0; $i < count($path);) {
            if ($path[$i] == '..') {
                if ($i == 0) {
                    // the case is there is nothing to contract from, example: `http://domain.pl/../img.png
                    // browsers don't care about it so we neither
                    array_shift($path);
                } else {
                    array_splice($path, $i - 1, 2);
                    $i--;
                }
            } else {
                $i++;
            }
        }

        $parsed['path'] = implode('/', $path);

        // if after merging path is empty, set it to "standard" empty path
        if ($parsed['path'] == '') {
            $parsed['path'] = self::EMPTY_PATH;
        }

        // Standardize url
        // Standardize: lowercase host
        $parsed['host'] = strtolower($parsed['host']);

        // fill in empty fields
        foreach (['scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'] as $field) {
            $parsed[$field] = $parsed[$field] ?? '';
        }

        // Standardize: sort query params
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
            ksort($query);
            $parsed['query'] = http_build_query($query);
        }

        if ($returnParsed) {
            return $parsed;
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