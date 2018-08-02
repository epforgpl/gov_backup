<?php

namespace Tests\Unit;

use App\Helpers\Reply;
use Tests\TestCase;

class StandardizeUrlTest extends TestCase
{
    public function testExceptionInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        Reply::createAbsoluteStandardizedUrl('','');
    }

    public function testExceptionMissingBase()
    {
        $this->expectException(\Exception::class);

        Reply::createAbsoluteStandardizedUrl('/','');
    }

    private function assertStandardUrl($expected, $base_url, $relative_url) {
        $standardized = Reply::createAbsoluteStandardizedUrl($relative_url, $base_url);

        $this->assertEquals($expected, $standardized);
    }

    public function testRootUrl()
    {
        $this->assertStandardUrl('http://domain.pl/', 'http://domain.pl', '/');
        $this->assertStandardUrl('https://domain.pl/', 'https://domain.pl', '/');
        $this->assertStandardUrl('https://domain.pl/', 'https://domain.pl/', '/');
        $this->assertStandardUrl('https://domain.pl/', 'https://domain.pl/somedir/somepath', '/');
    }

    public function testAbsoluteUrl()
    {
        $this->assertStandardUrl('http://domain.pl/osiem','http://domain.pl/raz/dwa', '/osiem');
    }

    public function testExternalUrl()
    {
        $this->assertStandardUrl('https://external.pl/trzy/cztery','http://domain.pl/raz/dwa', 'https://external.pl/trzy/cztery');
    }

    public function testRelativeUrl()
    {
        $this->assertStandardUrl('http://domain.pl/raz/dwa/trzy/cztery', 'http://domain.pl/raz/dwa/', 'trzy/cztery');
        $this->assertStandardUrl('http://domain.pl/raz/trzy/cztery', 'http://domain.pl/raz/file', 'trzy/cztery');
    }

    public function testUrlContracting()
    {
        $this->assertStandardUrl('http://domain.pl/raz/osiem','http://domain.pl/raz/dwa/trzy/file', '../../osiem');
        $this->assertStandardUrl('http://domain.pl/raz/dwa/osiem','http://domain.pl/raz/dwa/trzy/file', 'siedem/../../osiem');
    }

    public function testUrlContractingTooManyLevels()
    {
        $this->assertStandardUrl('http://domain.pl/','http://domain.pl/raz/dwa', '../../../../../../../');
    }

    public function testUrlContractingSingleDot()
    {
        $this->assertStandardUrl('http://domain.pl/raz/dwa','http://domain.pl/', '/./raz/dwa');
        $this->assertStandardUrl('http://domain.pl/raz/dwa2','http://domain.pl/', './raz/dwa2');
        $this->assertStandardUrl('http://domain.pl/raz/dwa/trzy/cztery','http://domain.pl/raz/dwa/', 'trzy/./cztery');
    }

    public function testDuplicatedSlashes()
    {
        $this->assertStandardUrl('http://domain.pl/raz/dwa/trzy/cztery','http://domain.pl/raz/dwa//', 'trzy/cztery');
        $this->assertStandardUrl('http://domain.pl/raz/dwa/trzy/cztery','http://domain.pl///raz/dwa/', 'trzy/cztery');
        $this->assertStandardUrl('http://domain.pl/raz/dwa/trzy/cztery','http://domain.pl/raz/dwa/', 'trzy///cztery');
        $this->assertStandardUrl('http://domain.pl/raz/dwa/trzy/cztery','http://domain.pl///raz/dwa//', 'trzy///cztery');
    }

    public function testQueryOtherPath()
    {
        $this->assertStandardUrl('http://domain.pl/inny?var2=param2','http://domain.pl/raz?var=param', 'inny?var2=param2');
    }

    public function testQuerySamePath()
    {
        $this->assertStandardUrl('http://domain.pl/raz?var2=param2','http://domain.pl/raz?var=param', '?var2=param2');
    }

    public function testQueryNoPath()
    {
        $this->assertStandardUrl('http://domain.pl/raz?var=param2','http://domain.pl/raz?var=param', '?var=param2');
    }

    public function testFragmentExternal() {
        $this->assertStandardUrl('http://domain.pl/raz#fragment','http://other.pl/raz?var=param', 'http://domain.pl/raz#fragment');
    }

    public function testUnparseArray() {
        $parsed = Reply::createAbsoluteStandardizedUrl('http://domain.pl/','http://other.pl', true);
        self::assertEquals('http://domain.pl/', Reply::unparse_url($parsed));
    }

    public function testUnparseArrayNoDomainSlash() {
        $parsed = Reply::createAbsoluteStandardizedUrl('http://domain.pl','http://other.pl', true);
        self::assertEquals('http://domain.pl/', Reply::unparse_url($parsed));
    }
}
