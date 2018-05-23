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
                if (stripos($url, 'mailto:') === 0
                || stripos($url, 'data:') === 0
                || stripos($url, 'file:') === 0) {
                    // don't rewrite them
                    continue;
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
}