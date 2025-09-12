<?php

declare(strict_types=1);

namespace PhpSocks\Tests\Integration;

use PhpSocks\Client;
use PhpSocks\Exception\PhpSocksException;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
final class ClientTest extends TestCase
{
    private $socksHost = 'dante';
    private $socksPort = 1080;

    /**
     * @test
     */
    public function connectTcp(): void
    {
        $client = new Client([
            'host' => $this->socksHost,
            'port' => $this->socksPort,
            'timeout' => 5,
            'connect_timeout' => 5,
        ]);

        $stream = $client->connect('tcp://node-pingpong:4000');

        $stream->write("ping\n");
        $response = $stream->read(1024);
        $stream->close();

        $this->assertStringContainsString('pong', $response);
    }

    /**
     * @test
     */
    public function associateUdp(): void
    {
        $client = new Client([
            'host' => $this->socksHost,
            'port' => $this->socksPort,
            'timeout' => 1,
            'connect_timeout' => 1,
        ]);

        $stream = $client->associate('udp://node-pingpong:4001');

        $stream->write("ping");
        $response = $stream->read(10);
        $stream->close();

        $this->assertStringContainsString('pong', $response);
    }

    /**
     * @test
     */
    public function tcpWriteAfterCloseThrowsException(): void
    {
        $client = new Client([
            'host' => $this->socksHost,
            'port' => $this->socksPort,
            'timeout' => 5,
            'connect_timeout' => 5,
        ]);

        $stream = $client->connect('tcp://node-pingpong:4000');
        $stream->close();

        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Socket is inoperable.');
        $stream->write("should-fail\n");
    }

    /**
     * @test
     */
    public function tcpReadAfterCloseThrowsException(): void
    {
        $client = new Client([
            'host' => $this->socksHost,
            'port' => $this->socksPort,
            'timeout' => 5,
            'connect_timeout' => 5,
        ]);

        $stream = $client->connect('tcp://node-pingpong:4000');
        $stream->write("should-fail\n");
        $stream->close();

        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Socket is inoperable.');
        $stream->read(1);
    }

    /**
     * @test
     */
    public function udpWriteAfterCloseThrowsException(): void
    {
        $client = new Client([
            'host' => $this->socksHost,
            'port' => $this->socksPort,
            'timeout' => 1,
            'connect_timeout' => 1,
        ]);

        $stream = $client->associate('udp://node-pingpong:4001');
        $stream->close();

        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Socket is inoperable.');
        $stream->write("should-fail");
    }

    /**
     * @test
     */
    public function udpReadAfterCloseThrowsException(): void
    {
        $client = new Client([
            'host' => $this->socksHost,
            'port' => $this->socksPort,
            'timeout' => 1,
            'connect_timeout' => 1,
        ]);

        $stream = $client->associate('udp://node-pingpong:4001');
        $stream->close();

        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Socket is inoperable.');
        $stream->read(1);
    }
}
