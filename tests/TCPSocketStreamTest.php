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

use PhpSocks\Exception\PhpSocksException;
use PhpSocks\TCPSocketStream;
use PHPUnit\Framework\TestCase;

final class TCPSocketStreamTest extends TestCase
{
    /**
     * @test
     */
    public function readsAll(): void
    {
        $sock = fopen('php://memory', 'r+');
        fwrite($sock, 'Hello World');
        rewind($sock);
        $stream = new TCPSocketStream($sock);
        $this->assertEquals('Hello World', $stream->readAll());
        fclose($sock);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenReadingAllFromClosedSocket(): void
    {
        $sock = fopen('php://memory', 'r+');
        fwrite($sock, 'Hello World');
        rewind($sock);
        $stream = new TCPSocketStream($sock);
        fclose($sock);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Inoperable socket');
        $stream->readAll();
    }

    /**
     * @test
     */
    public function reads(): void
    {
        $sock = fopen('php://memory', 'r+');
        fwrite($sock, 'Hello World');
        rewind($sock);
        $stream = new TCPSocketStream($sock);
        $this->assertEquals('Hello', $stream->read(5));
        fclose($sock);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenReadingFromClosedSocket(): void
    {
        $sock = fopen('php://memory', 'r+');
        fwrite($sock, 'Hello World');
        rewind($sock);
        $stream = new TCPSocketStream($sock);
        fclose($sock);
        $this->expectExceptionMessage('Inoperable socket');
        $this->expectException(PhpSocksException::class);
        $stream->read(5);
    }

    /**
     * @test
     */
    public function writes(): void
    {
        $sock = fopen('php://memory', 'r+');
        $stream = new TCPSocketStream($sock);
        $this->assertEquals(11, $stream->write('Hello World'));
        rewind($sock);
        $this->assertEquals('Hello World', fread($sock, 11));
        fclose($sock);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenWritingToClosedSocket(): void
    {
        $sock = fopen('php://memory', 'r+');
        $stream = new TCPSocketStream($sock);
        fclose($sock);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Inoperable socket');
        $stream->write('Hello World');
    }

    /**
     * @test
     */
    public function closesSocket(): void
    {
        $sock = fopen('php://memory', 'r+');
        $stream = new TCPSocketStream($sock);
        $stream->close();
        $this->assertFalse(is_resource($sock));
    }
}
