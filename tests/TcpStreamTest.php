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
use PhpSocks\TcpStream;
use PHPUnit\Framework\TestCase;

final class TcpStreamTest extends TestCase
{
    /**
     * @test
     */
    public function reads(): void
    {
        $sock = fopen('php://memory', 'r+');
        fwrite($sock, 'Hello World');
        rewind($sock);
        $stream = new TcpStream($sock);
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
        $stream = new TcpStream($sock);
        fclose($sock);
        $this->expectExceptionMessage('Socket is inoperable.');
        $this->expectException(PhpSocksException::class);
        $stream->read(5);
    }

    /**
     * @test
     */
    public function writes(): void
    {
        $sock = fopen('php://memory', 'r+');
        $stream = new TcpStream($sock);
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
        $stream = new TcpStream($sock);
        fclose($sock);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Socket is inoperable.');
        $stream->write('Hello World');
    }

    /**
     * @test
     */
    public function closesSocket(): void
    {
        $sock = fopen('php://memory', 'r+');
        $stream = new TcpStream($sock);
        $stream->close();
        $this->assertFalse(is_resource($sock));
    }
}
