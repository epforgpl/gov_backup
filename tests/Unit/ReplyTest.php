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
div.doble { background-image: url("http://govbackup.org/get/img_tree.gif"), url("http://govbackup.org/get/paper.gif");}
CSS;

        self::assertEquals($expected, Reply::replyCss($css, function($url, $type) {
            return 'http://govbackup.org/' . $type . '/' . $url;
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

        self::assertEquals($expected, Reply::replyCss($css, function($url, $type) {
            return 'http://govbackup.org/' . $type . '/' . $url;
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
    <link rel="shortcut icon" type="image/x-icon" href="http://govbackup.org/get/favicon.ico">
    <meta property="og:title" content="title">
    <meta property="og:image" content="http://govbackup.org/get/image.jpg">
    <script src="http://govbackup.org/get/inthehead.js"></script>  
</head>
<body>
    <a href="http://govbackup.org/web/http://archived.org/path?query" target="_top">link</a>
    <a href="http://external.org" target="_blank">link</a>

    <a href="#fragment">link</a>
    <a href="mailto:mail@example.org">link</a>
    <a href="data:1234">link</a>
    <a href="file:/etc/passwd">link</a>   
        
    <script src="http://govbackup.org/get/inthebody.js"></script>
    <img src="http://govbackup.org/get/img.jpg" data-original="http://govbackup.org/get/value.img">
    <input src="http://govbackup.org/get/submit.gif">
</body>
</html>

HTML;

        self::assertEquals($expected, Reply::replyHtml($html, function($url, $type) {
            if ($url == 'http://external.org') {
                return null;
            }
            return 'http://govbackup.org/' . $type . '/' . $url;
        }));
    }
}
