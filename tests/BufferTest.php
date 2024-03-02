<?php

/*
 * This file is part of the PhpSocks package.
 *
 * (c) 2024 Roman Vazhynskyi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace PhpSocks\Tests;

use PhpSocks\Buffer;
use PhpSocks\Exception\InvalidArgumentException;
use PhpSocks\Exception\PhpSocksException;
use PHPUnit\Framework\TestCase;

final class BufferTest extends TestCase
{
    /**
     * @test
     */
    public function readsUInt8(): void
    {
        $buf = new Buffer(pack('C2', 0x01, 0x02));
        $this->assertEquals(0x01, $buf->readUInt8(0));
        $this->assertEquals(0x02, $buf->readUInt8(1));
    }

    /**
     * @test
     */
    public function writesUInt8(): void
    {
        $buf = new Buffer();
        $buf->writeUInt8(0x05)
            ->writeUInt8(0x08);
        $this->assertEquals(pack('C2', 0x05, 0x08), $buf->flush());
    }

    /**
     * @test
     */
    public function writesUInt16(): void
    {
        $buf = new Buffer();
        $buf->writeUInt16(1245)
            ->writeUInt16(7897);
        $this->assertEquals(pack('n2', 1245, 7897), $buf->flush());
    }

    /**
     * @test
     */
    public function writesString(): void
    {
        $buf = new Buffer();
        $buf->writeString('Hello')
            ->writeString('World');
        $this->assertEquals('HelloWorld', $buf->flush());
    }

    /**
     * @test
     * @dataProvider provideDataForUInt8WriteFailureTest
     */
    public function throwsExceptionWhenWritingInvalidUInt8(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value exceeds uint8 range (0-255)');
        (new Buffer())->writeUInt8($value);
    }

    public static function provideDataForUInt8WriteFailureTest(): iterable
    {
        return [
            'tries to write a negative int' => [
                'value' => -1,
            ],
            'tries too write an int > 255' => [
                'value' => 256,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideDataForUInt16WriteFailureTest
     */
    public function throwsExceptionWhenWritingInvalidUInt16(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value exceeds uint16 range (0-65535)');
        (new Buffer())->writeUInt16($value);
    }

    public static function provideDataForUInt16WriteFailureTest(): iterable
    {
        return [
            'tries to write a negative int' => [
                'value' => -1,
            ],
            'tries too write an int > 65535' => [
                'value' => 65536,
            ],
        ];
    }

    /**
     * @test
     */
    public function throwsExceptionWhenReadingValueThatIsOutOfRange()
    {
        $buf = new Buffer();
        $buf->writeUInt8(0x01);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Offset is out of buffer bounds');
        $buf->readUInt8(1);
    }
}
