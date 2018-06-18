<?php

namespace Tests\Unit;

use App\Helpers\EpfHelpers;
use Tests\TestCase;

class EpfHelpersTest extends TestCase
{
    public function testJsonComments()
    {
        $input = <<<TEXT
{
  "val": 1,
  "//url": "so",
  // comment
  "key": {
  }
}
TEXT;
        $expected = <<<TEXT
{
  "val": 1,
  "//url": "so",
  "key": {
  }
}
TEXT;
        self::assertEquals($expected, EpfHelpers::strip_json_comments($input));
    }
}
