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

/**
 * @internal
 */
final class Connection
{
    private TCPSocketStream $socket;

    /**
     * @throws PhpSocksException
     */
    public function establish(string $host, int $port)
    {
        if (!$sock = stream_socket_client('tcp://' . $host . ':' . $port, $_, $err)) {
            throw new PhpSocksException('Failed to connect to the SOCKS5 server: ' . $err);
        }
        $this->socket = new TCPSocketStream($sock);
    }

    /**
     * @throws PhpSocksException
     */
    public function write(Buffer $buf): void
    {
        $this->socket->write($buf->flush());
    }

    /**
     * @throws PhpSocksException
     */
    public function read(int $length): Buffer
    {
        return new Buffer($this->socket->read($length));
    }

    public function stream(): TCPSocketStream
    {
        return $this->socket;
    }
}
