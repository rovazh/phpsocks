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

use PhpSocks\Client;
use PHPUnit\Framework\TestCase;

/**
 * @group Functional
 */
final class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function connectsTcp(): void
    {
        $client = new Client([
            'host' => '127.0.0.1',
            'port' => 1080,
            'connect_timeout' => 1,
            'timeout' => 1,
        ]);
        $stream =  $client->connect('tcp://ping-pong-server:1333');
        $stream->write("ping\n");
        $this->assertEquals("pong\n", $stream->read(4));
        $stream->close();
    }

    /**
     * @test
     */
    public function connectsTls(): void
    {
        $client = new Client([
            'host' => '127.0.0.1',
            'port' => 1080,
            'connect_timeout' => 1,
            'timeout' => 1,
        ]);
        $stream =  $client->connect('tls://ping-pong-server:1555', [
            'tls' => [
                'cafile' => './tests/ping-pong-server/testCA.pem',
                'verify_peer_name' => false,
                'verify_peer' => false,
            ],
        ]);
        $stream->write("ping\n");
        $this->assertEquals("pong\n", $stream->read(4));
        $stream->close();
    }
}
