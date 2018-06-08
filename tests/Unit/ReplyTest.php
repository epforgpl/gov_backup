<?php

namespace Tests\Unit;

use App\Helpers\Reply;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    public function testCss()
    {
        $css = <<<CSS
abbr[title], 
abbr[data-original-title] {
  text-decoration: underline; background-image: url('http://archived.org/image.jpg');
}
div.archived { background-image: url('http://archived.org/image.jpg'); }
div.double { background-image: url("http://archived.org/image.jpg"); }
div.noquotes { background-image: url(http://archived.org/image.jpg); }
div.doble { background-image: url("img_tree.gif"), url("paper.gif");}
CSS;

        $expected = <<<CSS
abbr[title], 
abbr[data-original-title] {
  text-decoration: underline; background-image: url('http://govbackup.org/get/http://archived.org/image.jpg');
}
div.archived { background-image: url('http://govbackup.org/get/http://archived.org/image.jpg'); }
div.double { background-image: url("http://govbackup.org/get/http://archived.org/image.jpg"); }
div.noquotes { background-image: url(http://govbackup.org/get/http://archived.org/image.jpg); }
div.doble { background-image: url("http://govbackup.org/get/http://archived.org/assets/img_tree.gif"), url("http://govbackup.org/get/http://archived.org/assets/paper.gif");}
CSS;

        self::assertEquals($expected, Reply::replyCss($css, 'http://archived.org/assets/', function(array $parsed_url, string $type) {
            return 'http://govbackup.org/' . $type . '/' . Reply::unparse_url($parsed_url);
        }));
    }

    public function testCssMultiline()
    {
        $css = <<<CSS
div.breakline { background-image: url(
'http://archived.org/image.jpg'); }
div.breakline { background-image: url( 'http://archived.org/image.jpg'
); }
CSS;

        $expected = <<<CSS
div.breakline { background-image: url('http://govbackup.org/get/http://archived.org/image.jpg'); }
div.breakline { background-image: url('http://govbackup.org/get/http://archived.org/image.jpg'); }
CSS;

        self::assertEquals($expected, Reply::replyCss($css, 'http://archived.org/assets/', function(array $parsed_url, string $type) {
            return 'http://govbackup.org/' . $type . '/' . Reply::unparse_url($parsed_url);
        }));
    }

    private function assertRepliedHtml($expected, $html, $base_url = 'http://archived.org/') {
        self::assertEquals($expected, Reply::replyHtml($html, $base_url, function($parsed_url, $type) {
            if ($parsed_url['host'] == 'external.org') {
                return null;
            }
            return 'http://govbackup.org/' . $type . '/' . Reply::unparse_url($parsed_url);
        }));
    }

    public function testHtmlBasic() {
        $html = <<<HTML
<html>
<head>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    <meta property="og:title" content="title" />
    <meta property="og:image" content="image.jpg"/>
    <script src="inthehead.js"/>  
</head>
<body>
    <a href="http://archived.org/path?query">link</a>
    <a href="http://external.org">link</a>

    <a href="#fragment">link</a>
    <a href="mailto:mail@example.org">link</a>
    <a href="data:1234">link</a>
    <a href="file:/etc/passwd">link</a>   
        
    <script src="inthebody.js"/>
    <img src="img.jpg" data-original="value.img"/>
    <input  src="submit.gif"/>
</body>
</html>
HTML;

        $expected = <<<HTML
<html>
<head>
    <link rel="shortcut icon" type="image/x-icon" href="http://govbackup.org/get/http://archived.org/favicon.ico">
    <meta property="og:title" content="title">
    <meta property="og:image" content="http://govbackup.org/get/http://archived.org/image.jpg">
    <script src="http://govbackup.org/get/http://archived.org/inthehead.js"></script>  
</head>
<body>
    <a href="http://govbackup.org/web/http://archived.org/path?query=" target="_top">link</a>
    <a href="http://external.org" target="_blank">link</a>

    <a href="#fragment">link</a>
    <a href="mailto:mail@example.org">link</a>
    <a href="data:1234">link</a>
    <a href="file:/etc/passwd">link</a>   
        
    <script src="http://govbackup.org/get/http://archived.org/inthebody.js"></script>
    <img src="http://govbackup.org/get/http://archived.org/img.jpg" data-original="http://govbackup.org/get/http://archived.org/value.img">
    <input src="http://govbackup.org/get/http://archived.org/submit.gif">
</body>
</html>

HTML;

        self::assertRepliedHtml($expected, $html);
    }

    public function testHtmlwithBaseTag() {
        $html = <<<HTML
<html>
<head>
    <base href="http://archived.org/base/">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    <script src="inthehead.js"/>  
</head>
<body>
    <a href="http://archived.org/path?query">link</a>
    <a href="http://external.org">link</a>
    
    <a href="link">link</a>
    <a href="mailto:mail@example.org">link</a>
    <a href="data:1234">link</a>
    <a href="file:/etc/passwd">link</a>   
        
    <script src="inthebody.js"/>
    <img src="img.jpg" data-original="value.img"/>
    <input  src="submit.gif"/>
</body>
</html>
HTML;

        $expected = <<<HTML
<html>
<head>
    
    <link rel="shortcut icon" type="image/x-icon" href="http://govbackup.org/get/http://archived.org/base/favicon.ico">
    <script src="http://govbackup.org/get/http://archived.org/base/inthehead.js"></script>  
</head>
<body>
    <a href="http://govbackup.org/web/http://archived.org/path?query=" target="_top">link</a>
    <a href="http://external.org" target="_blank">link</a>
    
    <a href="http://govbackup.org/web/http://archived.org/base/link" target="_top">link</a>
    <a href="mailto:mail@example.org">link</a>
    <a href="data:1234">link</a>
    <a href="file:/etc/passwd">link</a>   
        
    <script src="http://govbackup.org/get/http://archived.org/base/inthebody.js"></script>
    <img src="http://govbackup.org/get/http://archived.org/base/img.jpg" data-original="http://govbackup.org/get/http://archived.org/base/value.img">
    <input src="http://govbackup.org/get/http://archived.org/base/submit.gif">
</body>
</html>

HTML;

        self::assertRepliedHtml($expected, $html);
    }

    public function testFragmentswithBaseTag() {
        $html = <<<HTML
<html>
<head>
    <base href="http://archived.org/base/">
</head>
<body>
    <a href="#fragment">simple fragment</a>
    <a href="http://archived.org/base/#fragment">fragment to base tag</a>
    <a href="http://archived.org/#fragment">fragment to base url</a>
    <a href="http://external.org/#fragment">fragment external</a>
</body>
</html>
HTML;
        $expected = <<<HTML
<html>
<head>
    
</head>
<body>
    <a href="http://govbackup.org/web/http://archived.org/base/#fragment" target="_top">simple fragment</a>
    <a href="http://govbackup.org/web/http://archived.org/base/#fragment" target="_top">fragment to base tag</a>
    <a href="#fragment">fragment to base url</a>
    <a href="http://external.org/#fragment" target="_blank">fragment external</a>
</body>
</html>

HTML;

        self::assertRepliedHtml($expected, $html, 'http://archived.org');
    }


    public function testLocalFragmentSolo() {
        $html = <<<HTML
<html><body>
    <a href="#fragment">link</a>
</body></html>
HTML;
        $expected = <<<HTML
<html><body>
    <a href="#fragment">link</a>
</body></html>

HTML;

        self::assertRepliedHtml($expected, $html);
    }

    public function testLocalFragmentPath() {
        $html = <<<HTML
<html><body>
    <a href="/path#fragment">link</a>
</body></html>
HTML;
        $expected = <<<HTML
<html><body>
    <a href="#fragment">link</a>
</body></html>

HTML;

        self::assertRepliedHtml($expected, $html, 'http://archived.org/path');
    }

    public function testLocalFragmentFull() {
        $html = <<<HTML
<html><body>
    <a href="http://archived.org/path#fragment">link</a>
</body></html>
HTML;
        $expected = <<<HTML
<html><body>
    <a href="#fragment">link</a>
</body></html>

HTML;

        self::assertRepliedHtml($expected, $html, 'http://archived.org/path');
    }

    public function testLocalFragmentFullUppercase() {
        $html = <<<HTML
<html><body>
    <a href="http://archived.org/path#fragment">link</a>
</body></html>
HTML;
        $expected = <<<HTML
<html><body>
    <a href="#fragment">link</a>
</body></html>

HTML;

        self::assertRepliedHtml($expected, $html, 'http://ARCHIVED.org/path');
    }

}
