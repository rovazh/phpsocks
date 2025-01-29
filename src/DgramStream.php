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

namespace PhpSocks;

use PhpSocks\Exception\PhpSocksException;
use PhpSocks\Proto\Dgram;

final class DgramStream implements Stream
{
    /**
     * @var resource|closed-resource
     */
    private $sock;
    private TcpStream $controlStream;
    private string $bndAddr;
    private int $bndPort;
    private string $destAddr;
    private int $destPort;

    /**
     * @param resource|closed-resource $sock
     */
    private function __construct($sock, TcpStream $controlStream, string $bndAddr, int $bndPort, string $destAddr, int $destPort)
    {
        $this->sock = $sock;
        $this->controlStream = $controlStream;
        $this->bndAddr = $bndAddr;
        $this->bndPort = $bndPort;
        $this->destAddr = $destAddr;
        $this->destPort = $destPort;
    }

    /**
     * @param array{timeout?: int, connect_timeout?: int} $options
     *
     * @throws PhpSocksException
     */
    public static function create(
        TcpStream $controlStream,
        string $bndAddr,
        int $bndPort,
        string $destAddr,
        int $destPort,
        array $options = []
    ): self {
        if (!$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) {
            throw new PhpSocksException('Unable to create stream: ' . socket_strerror(socket_last_error()));
        }
        if (isset($options['timeout'])) {
            if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $options['timeout'], 'usec' => 0])) {
                throw new PhpSocksException('Failed to set timeout on the stream: ' . socket_strerror(socket_last_error()));
            }
            if (!socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $options['timeout'], 'usec' => 0])) {
                throw new PhpSocksException('Failed to set timeout on the stream: ' . socket_strerror(socket_last_error()));
            }
        }
        return new self($socket, $controlStream, $bndAddr, $bndPort, $destAddr, $destPort);
    }

    /**
     * {@inheritDoc}
     */
    public function read(int $length): string
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Inoperable socket');
        }
        if ($this->controlStream->eof()) {
            throw new PhpSocksException('Failed to read from the stream: the control connection is closed');
        }
        $bytesReceived = socket_recvfrom($this->sock, $reply, Dgram::MAX_HEADER_LENGTH + $length, 0, $from_, $port_);
        if ($bytesReceived === false) {
            throw new PhpSocksException('Failed to read from the stream: ' . socket_strerror(socket_last_error()));
        }
        return Dgram::parse(new Buffer($reply), $length);
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $data): int
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Inoperable socket');
        }
        if ($this->controlStream->eof()) {
            throw new PhpSocksException('Failed to write to the stream: the control connection is closed');
        }
        $buf = Dgram::prep($this->destAddr, $this->destPort, $data);
        $headerSize = $buf->getSize() - strlen($data);
        $dgram = $buf->flush();
        $sent = socket_sendto($this->sock, $dgram, strlen($dgram), 0, $this->bndAddr, $this->bndPort);
        if ($sent === false) {
            throw new PhpSocksException('Failed to write to the stream: ' . socket_strerror(socket_last_error()));
        }
        return $sent - $headerSize;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if (is_resource($this->sock)) {
            fclose($this->sock);
        }
        $this->controlStream->close();
    }
}
