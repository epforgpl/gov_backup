<?php
/**
 * Created by PhpStorm.
 * User: kmadejski
 * Date: 23.05.18
 * Time: 13:00
 */

namespace Tests\Unit;


use App\Helpers\Reply;
use Tests\TestCase;

class DomTest extends TestCase
{
    public function testErrorOnScript() {
        $html = <<<HTML
<html class="no-js" lang="pl-pl"><head>
	<script>
		GLOBAL = {
			url: '/',
			lng_code: "pl",
			cookie_info: '<p>Serwis internetowy Krajowej Rady Sądownictwa przechowuje informacje i uzyskuje dostęp do informacji już przechowanych potrzebnych do prawidłowego działania strony internetowej oraz w celach statystycznych w tzw. plikach "Cookies" na komputerze użytkownika.</p> <p>Brak zmiany ustawień przeglądarki internetowej oznacza zgodę na przechowywanie i uzyskiwanie dostępu do tych informacji na komputerze użytkownika przez serwis internetowy Krajowej Rady Sądownictwa.</p>'
		}
	</script>
</head>
</html>
HTML;
        $d = new \DOMDocument();
        $this->expectException(\ErrorException::class);
        $d->loadHTML($html, LIBXML_HTML_NODEFDTD);
    }

    public function testSilenceWarningOnScript() {
        $html = <<<HTML
<html class="no-js" lang="pl-pl"><head>
	<script>
		GLOBAL = {
			url: '/',
			lng_code: "pl",
			cookie_info: '<p>Serwis internetowy Krajowej Rady Sądownictwa przechowuje informacje i uzyskuje dostęp do informacji już przechowanych potrzebnych do prawidłowego działania strony internetowej oraz w celach statystycznych w tzw. plikach "Cookies" na komputerze użytkownika.</p> <p>Brak zmiany ustawień przeglądarki internetowej oznacza zgodę na przechowywanie i uzyskiwanie dostępu do tych informacji na komputerze użytkownika przez serwis internetowy Krajowej Rady Sądownictwa.</p>'
		}
	</script>
</head>
</html>
HTML;
        $d = new \DOMDocument();
        $d->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        self::assertTrue(true);
    }

    private function assertEncodingDetected($html, $encoding) {
        $d = new \DOMDocument();
        $d->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);

        $actualEncoding = Reply::detectEncoding($d);
        self::assertEquals($encoding, $actualEncoding);
    }

    public function testDetectEncodingHTML4() {
        $html = <<<HTML
<html>
<head>
    <title> Vietnamese - Tiếng Việt</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
  </head>
<body></body>
</html>
HTML;

        $this->assertEncodingDetected($html, 'utf-8');
    }

    public function testDetectEncodingHTML5() {
        $html = <<<HTML
<html>
<head>
    <title> Vietnamese - Tiếng Việt</title>
    <meta charset="utf-8">
  </head>
<body></body>
</html>
HTML;

        $this->assertEncodingDetected($html, 'utf-8');
    }
}