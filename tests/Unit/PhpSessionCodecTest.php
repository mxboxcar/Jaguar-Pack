<?php

namespace Tests\Unit;

use App\Support\Legacy\PhpSessionCodec;
use PHPUnit\Framework\TestCase;

class PhpSessionCodecTest extends TestCase
{
    public function test_round_trip_php_serialize_handler_format(): void
    {
        $raw = 'user_id|s:5:"ab|cd";user_role|s:0:"";cart|a:2:{i:0;s:1:"x";s:1:"k";a:1:{i:1;d:1.5;}}flag|b:0;n|N;i|i:-3;';
        $data = PhpSessionCodec::decode($raw);
        $this->assertSame(['user_id' => 'ab|cd', 'user_role' => '', 'cart' => [0 => 'x', 'k' => [1 => 1.5]], 'flag' => false, 'n' => null, 'i' => -3], $data);
        $this->assertSame($raw, PhpSessionCodec::encode($data));
    }

    public function test_broken_data_keeps_valid_prefix(): void
    {
        $this->assertSame(['a' => 'x'], PhpSessionCodec::decode('a|s:1:"x";b|s:9:"short";'));
        $this->assertSame([], PhpSessionCodec::decode(''));
    }

    public function test_objects_are_not_instantiated(): void
    {
        $d = PhpSessionCodec::decode('o|O:8:"stdClass":1:{s:1:"a";i:1;}');
        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $d['o']);
    }
}
