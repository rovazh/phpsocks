<?php

/*
 * This file is part of the PhpSocks package.
 *
 * (c) 2025 Roman Vazhynskyi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace PhpSocks\Tests;

use OutOfBoundsException;
use PhpSocks\Buffer;
use PhpSocks\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class BufferTest extends TestCase
{
    /**
     * @test
     */
    public function readsUInt8(): void
    {
        $buf = new Buffer("\x05\xFF");
        $this->assertEquals(5, $buf->readUInt8());
        $this->assertEquals(255, $buf->readUInt8());
    }

    /**
     * @test
     */
    public function readsUint16(): void
    {
        $buf = new Buffer("\xFF\xFF");
        $this->assertEquals(65535, $buf->readUint16());
    }

    /**
     * @test
     */
    public function readsString(): void
    {
        $buf = new Buffer('test');
        $this->assertEquals('tes', $buf->readString(3));
    }

    /**
     * @test
     */
    public function writesString(): void
    {
        $buf = new Buffer();
        $buf->writeString('test');
        $this->assertEquals('test', $buf->flush());
    }

    /**
     * @test
     */
    public function writesUInt8(): void
    {
        $buf = new Buffer();
        $buf->writeUInt8(255);
        $buf->writeUInt8(3);
        $this->assertEquals("\xFF\x03", $buf->flush());
    }

    /**
     * @test
     * @dataProvider provideInvalidUint8Values
     */
    public function throwsExceptionWhenUInt8ValueExceedsRange(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $buf = new Buffer();
        $buf->writeUInt8($value);
    }

    public static function provideInvalidUint8Values(): array
    {
        return [
            '> 255' => [
                'value' => 256,
            ],
            '< 0' => [
                'value' => -1,
            ]
        ];
    }

    /**
     * @test
     */
    public function writesUInt16(): void
    {
        $buf = new Buffer();
        $buf->writeUInt16(2025);
        $this->assertEquals("\x07\xE9", $buf->flush());
    }

    /**
     * @test
     * @dataProvider provideInvalidUint16Values
     */
    public function throwsExceptionWhenUInt16ValueExceedsRange(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $buf = new Buffer();
        $buf->writeUInt16($value);
    }

    public static function provideInvalidUint16Values(): array
    {
        return [
            '> 65535' => [
                'value' => 65536,
            ],
            '< 0' => [
                'value' => -1,
            ]
        ];
    }

    /**
     * @test
     */
    public function readUint8ThrowsExceptionIfOffsetExceedsRange(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $buf = new Buffer();
        $buf->readUint8();
    }

    /**
     * @test
     */
    public function readUint16ThrowsExceptionIfOffsetExceedsRange(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $buf = new Buffer("\xFF");
        $buf->readUint16();
    }
}
