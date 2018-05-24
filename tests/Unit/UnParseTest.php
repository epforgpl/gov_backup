<?php

namespace Tests\Unit;

use App\Helpers\Reply;
use Tests\TestCase;

class UnParseTest extends TestCase
{
    public function testUnparseFull()
    {
        $url = 'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }

    public function testUnparseEmptySchema()
    {
        $url = '//example.com';
        self::assertEquals('example.com', Reply::unparse_url(parse_url($url)));
    }

    public function testUnparseFull3()
    {
        $url = 'example.com/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }

    public function testUnparseHostAndFragment()
    {
        $url = 'example.com#myfragment';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }

    public function testUnparseHostAndPath()
    {
        $url = 'example.com/path/rel';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }

    public function testUnparseHost()
    {
        $url = 'example.com';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }

    public function testUnparsePath()
    {
        $url = '/path/file';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }

    public function testUnparseQuery()
    {
        $url = '?q1=a&q2=b';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }

    public function testUnparseFragment()
    {
        $url = '?q1=a#fragment';
        self::assertEquals($url, Reply::unparse_url(parse_url($url)));
    }
}
