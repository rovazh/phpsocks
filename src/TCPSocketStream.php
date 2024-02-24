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

/**
 * @internal
 */
final class TCPSocketStream implements Stream
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
     * {@inheritDoc}
     */
    public function readAll(): string
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Inoperable socket');
        }
        $data = stream_get_contents($this->sock);
        if (false === $data) {
            throw new PhpSocksException('Failed to read from the stream');
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function read(int $length): string
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Inoperable socket');
        }
        $data = fread($this->sock, $length);
        if (false === $data) {
            throw new PhpSocksException('Failed to read from the stream');
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $data): int
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Inoperable socket');
        }
        $bytesWritten = fwrite($this->sock, $data);
        if (false === $bytesWritten) {
            throw new PhpSocksException('Failed to write to the stream');
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
     * @param array<string, string|bool|int|array> $options
     *
     * @throws PhpSocksException
     */
    public function enableEncryption(array $options): void
    {
        if (!is_resource($this->sock)) {
            throw new PhpSocksException('Inoperable socket');
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
