<?php

/*
 * This file is part of the PhpSocks package.
 *
 * (c) 2024 Roman Vazhynskyi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PhpSocks;

use PhpSocks\Exception\PhpSocksException;
use RuntimeException;

/**
 * @internal
 */
final class Connection
{
    private ?TCPSocketStream $socket = null;

    /**
     * @throws PhpSocksException
     */
    public function establish(string $host, int $port): void
    {
        if (!$sock = stream_socket_client('tcp://' . $host . ':' . $port, $_, $err)) {
            throw new PhpSocksException('Failed to connect to the SOCKS5 server: ' . $err);
        }
        $this->socket = new TCPSocketStream($sock);
    }

    /**
     * @throws PhpSocksException
     * @throws RuntimeException
     */
    public function write(Buffer $buf): void
    {
        if (!$this->socket) {
            throw new RuntimeException('No connection established');
        }
        $this->socket->write($buf->flush());
    }

    /**
     * @throws PhpSocksException
     * @throws RuntimeException
     */
    public function read(int $length): Buffer
    {
        if (!$this->socket) {
            throw new RuntimeException('No connection established');
        }
        return new Buffer($this->socket->read($length));
    }

    /**
     * @throws RuntimeException
     */
    public function stream(): TCPSocketStream
    {
        if (!$this->socket) {
            throw new RuntimeException('No connection established');
        }
        return $this->socket;
    }
}
