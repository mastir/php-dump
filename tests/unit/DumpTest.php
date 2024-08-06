<?php

declare(strict_types=1);

namespace Mastir\Test\PhpDump\unit;

use Mastir\PhpDump\PhpDump;

/**
 * @internal
 *
 * @coversNothing
 */
class DumpTest extends TestCase
{
    private PhpDump $dump;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->dump = new PhpDump();
    }

    public function testIntValues(): void
    {
        $this->assertEquals("\x01i\x01\x00\x00\x00\x02", $this->dump->value(1));
        $this->assertEquals("\x01i\x00\x00\x00\x00\x02", $this->dump->value(0));
        $this->assertEquals("\x01i\xfb\xff\xff\xff\x02", $this->dump->value(-5));
        $this->assertEquals("\x01i\xff\xff\xff\x7f\x02", $this->dump->value(2147483647));
    }

    public function testBoolValues(): void
    {
        $this->assertEquals("\x01b1\x02", $this->dump->value(true));
        $this->assertEquals("\x01b0\x02", $this->dump->value(false));
    }

    public function testStringValues(): void
    {
        $this->assertEquals("\x01s\x04\x00\x00\x00test\x02", $this->dump->value('test'));
        $this->assertEquals("\x01s\x01\x00\x00\x00\x00\x02", $this->dump->value("\x00"));
    }

    public function testFloatValues(): void
    {
        $this->assertEquals("\x01f\x00\x00\x00\x00\x02", $this->dump->value(0.0));
        $this->assertEquals("\x01f\x00\x00\x00\x80\x02", $this->dump->value(-0.0));
        $this->assertEquals("\x01f\x00\x00\x80\x3f\x02", $this->dump->value(1.0));
    }

    public function testArrayTitle(): void
    {
        $this->assertEquals('[]', $this->dump->arrayTitle([]));
        $this->assertEquals('[1]', $this->dump->arrayTitle([1]));
        $this->assertEquals('[1, 2, 3]', $this->dump->arrayTitle([1, 2, 3]));
        $this->assertEquals('["test"]', $this->dump->arrayTitle(['test']));
        $this->assertEquals('["test", 1, "foo"]', $this->dump->arrayTitle(['test', 1, 'foo']));
    }

    public function testArrayValues(): void
    {
        $this->assertEquals("\x01a\x02\x00\x00\x00[]\x00\x00\x00\x00\x02", $this->dump->arrayData([]));
        $this->assertEquals("\x01a". // <array>
            "\x03\x00\x00\x00[1]\x01\x00\x00\x00".// title="[1]"
            "\x01k\x00\x00\x00\x00". // <key index=0>
                "\x01i\01\x00\x00\x00\x02". // <int>1</int>
            "\x02\x02" // </key></array>
            , $this->dump->arrayData([1]));
    }

    public function testScope(): void
    {
        $expect = "\x01q". // <scope>
            "\x04\x00\x00\x00test". // title="test"
            "\x01\x00\x00\x00". // count=1
            "\x01v". // <value>
                "\x01\x00\x00\x00a". // <key>a</key>
                "\x01i\x01\x00\x00\x00\x02". // <int>1</int>
            "\x02".// </value>
        "\x02". // </scope>
        "\x06\x00\x00\x00\x00"; // refs,count=0

        $dump = new PhpDump();

        $dump->addScope('test', ['a' => 1]);
        $this->assertEquals($expect, $dump->build());
    }
}
