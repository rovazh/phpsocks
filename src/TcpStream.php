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

namespace PhpSocks;

use PhpSocks\Exception\PhpSocksException;

/**
 * @internal
 */
final class TcpStream implements Stream
{
    /**
     * @var resource|closed-resource
     */
    private $sock;

    /**
     * @param resource|closed-resource $sock
     */
    public function __construct($sock)
    {
        $this->sock = $sock;
    }

    /**
     * @param array{timeout?: int, connect_timeout?: float} $options
     *
     * @throws PhpSocksException
     */
    public static function create(
        string $addr,
        int $port,
        array $options = []
    ): self {
        if (isset($options['connect_timeout'])) {
            $sock = @stream_socket_client('tcp://' . $addr . ':' . $port, $_, $err, $options['connect_timeout']);
        } else {
            $sock = @stream_socket_client('tcp://' . $addr . ':' . $port, $_, $err);
        }
        if (!$sock) {
            throw new PhpSocksException('Failed to connect to the SOCKS server: ' . $err);
        }
        if (isset($options['timeout'])) {
            if (!@stream_set_timeout($sock, $options['timeout'])) {
                throw new PhpSocksException('Failed to set timeout on the stream.');
            }
        }
        return new TcpStream($sock);
    }

    /**
     * {@inheritDoc}
     */
    public function read(int $length): string
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Socket is inoperable.');
        }
        $data = fread($this->sock, $length);
        if (@stream_get_meta_data($this->sock)['timed_out']) {
            throw new PhpSocksException('Read operation timed out.');
        }
        if (false === $data) {
            throw new PhpSocksException('Failed to read from the stream.');
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $data): int
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Socket is inoperable.');
        }
        $bytesWritten = fwrite($this->sock, $data);
        if (@stream_get_meta_data($this->sock)['timed_out']) {
            throw new PhpSocksException('Write operation timed out.');
        }
        if (false === $bytesWritten) {
            throw new PhpSocksException('Failed to write to the stream.');
        }
        return $bytesWritten;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if (is_resource($this->sock)) {
            fclose($this->sock);
        }
    }

    /**
     * @throws PhpSocksException
     */
    public function eof(): bool
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Socket is inoperable.');
        }
        return feof($this->sock);
    }

    /**
     * @param array<string, string|bool|int|array> $options
     *
     * @throws PhpSocksException
     */
    public function enableEncryption(array $options): void
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Socket is inoperable.');
        }
        foreach ($options as $option => $value) {
            stream_context_set_option($this->sock, 'ssl', $option, $value);
        }
        set_error_handler(static function ($_, $err) {
            throw new PhpSocksException(str_replace('stream_socket_enable_crypto(): ', '', $err));
        });
        stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        restore_error_handler();
    }
}
