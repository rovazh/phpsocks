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

final class DetailsResponse implements Response
{
    private const VERSION = 0x05;
    private const SUCCESS = 0x00;
    private const RESERVED_OCTET = 0x00;
    private const ADDRESS_TYPE_DOMAIN_NAME = 0x03;
    private const ADDRESS_TYPE_IPV4 = 0x01;
    private const ADDRESS_TYPE_IPV6 = 0x04;

    /**
     * @var array<int, string>
     */
    public static array $errors = [
        0x01 => 'General SOCKS server failure',
        0x02 => 'Connection not allowed by ruleset',
        0x03 => 'Network unreachable',
        0x04 => 'Host unreachable',
        0x05 => 'Connection refused',
        0x06 => 'TTL expired',
        0x07 => 'Command not supported',
        0x08 => 'Address type not supported',
    ];
    private string $bndAddr = '';
    private int $bndPort = 0;

    /**
     * {@inheritDoc}
     */
    public function receive(Stream $stream): void
    {
        $buf = new Buffer($stream->read(4));

        $ver = $buf->readUInt8();
        $reply = $buf->readUInt8();
        $reserved = $buf->readUInt8();

        if (self::VERSION !== $ver) {
            throw new PhpSocksException('Invalid version');
        }
        if (self::SUCCESS !== $reply) {
            if (false === array_key_exists($reply, self::$errors)) {
                $err = 'General SOCKS server failure';
            } else {
                $err = self::$errors[$reply];
            }
            throw new PhpSocksException($err);
        }
        if (self::RESERVED_OCTET !== $reserved) {
            throw new PhpSocksException('Invalid reserved octet');
        }

        $addrType = $buf->readUInt8();
        if (self::ADDRESS_TYPE_DOMAIN_NAME === $addrType) {
            $length = (new Buffer($stream->read(1)))->readUInt8(); // Domain name length
            $this->bndAddr = $stream->read($length);
            $this->bndPort = (new Buffer($stream->read(2)))->readUint16();
        } elseif (self::ADDRESS_TYPE_IPV4 === $addrType) {
            $bndAddr = @inet_ntop($stream->read(4));
            if (false === $bndAddr) {
                throw new PhpSocksException('Invalid BND.ADDR');
            }
            $this->bndAddr = $bndAddr;
            $this->bndPort = (new Buffer($stream->read(2)))->readUint16();
        } elseif (self::ADDRESS_TYPE_IPV6 === $addrType) {
            $bndAddr = @inet_ntop($stream->read(16));
            if (false === $bndAddr) {
                throw new PhpSocksException('Invalid BND.ADDR');
            }
            $this->bndAddr = $bndAddr;
            $this->bndPort = (new Buffer($stream->read(2)))->readUint16();
        } else {
            throw new PhpSocksException('Address type returned by the SOCKS5 server is invalid');
        }
    }

    public function getBndAddr(): string
    {
        return $this->bndAddr;
    }

    public function getBndPort(): int
    {
        return $this->bndPort;
    }
}
