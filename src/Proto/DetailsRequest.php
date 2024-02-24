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

namespace PhpSocks\Proto;

use PhpSocks\Buffer;
use PhpSocks\Exception\PhpSocksException;
use PhpSocks\Stream;

final class DetailsRequest implements Request
{
    public const CMD_CONNECT = 0x01;
    private const VERSION = 0x05;
    private const RESERVED_OCTET = 0x00;
    private const ADDRESS_TYPE_DOMAIN_NAME = 0x03;
    private const ADDRESS_TYPE_IPV4 = 0x01;
    private const ADDRESS_TYPE_IPV6 = 0x04;

    private Buffer $buf;
    private string $host;
    private int $port;

    public function __construct(Buffer $buf, string $host, int $port)
    {
        $this->buf = $buf;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Stream $stream): void
    {
        $this->buf
            ->writeUInt8(self::VERSION)
            ->writeUInt8(self::CMD_CONNECT)
            ->writeUInt8(self::RESERVED_OCTET);

        $host = @inet_pton($this->host);
        if (false === $host) {
            $this->buf
                ->writeUInt8(self::ADDRESS_TYPE_DOMAIN_NAME)
                ->writeUInt8(strlen($this->host))
                ->writeString($this->host);
        } elseif (filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->buf
                ->writeUInt8(self::ADDRESS_TYPE_IPV4)
                ->writeString($host);
        } elseif (filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->buf
                ->writeUInt8(self::ADDRESS_TYPE_IPV6)
                ->writeString($host);
        } else {
            throw new PhpSocksException('Invalid destination host ' . $this->host);
        }

        $this->buf->writeUInt16($this->port);
        $stream->write($this->buf->flush());
    }
}
