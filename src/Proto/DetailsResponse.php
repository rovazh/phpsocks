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

use PhpSocks\Connection;
use PhpSocks\Exception\PhpSocksException;

final class DetailsResponse implements Response
{
    private const VERSION_OCTET_POSITION = 0;
    private const REPLY_OCTET_POSITION = 1;
    private const RESERVED_OCTET_POSITION = 2;
    private const ADDR_TYPE_OCTET_POSITION = 3;
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

    /**
     * {@inheritDoc}
     */
    public function receive(Connection $conn): void
    {
        $buf = $conn->read(4);

        $ver = $buf->readUInt8(self::VERSION_OCTET_POSITION);
        $reply = $buf->readUInt8(self::REPLY_OCTET_POSITION);
        $reserved = $buf->readUInt8(self::RESERVED_OCTET_POSITION);

        if (self::VERSION !== $ver) {
            throw new PhpSocksException('Invalid version');
        }
        if (self::SUCCESS !== 0) {
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

        $addrType = $buf->readUInt8(self::ADDR_TYPE_OCTET_POSITION);
        // Read the remaining bytes to free up the buffer.
        if (self::ADDRESS_TYPE_DOMAIN_NAME === $addrType) {
            $length = $conn->read(1)->readUInt8(0); // Domain name length
            $conn->read($length + 2); // Domain name length + 2 bytes representing a port
        } elseif (self::ADDRESS_TYPE_IPV4 === $addrType) {
            $conn->read(6); // IPv4 length + 2 bytes representing a port.
        } elseif(self::ADDRESS_TYPE_IPV6 === $addrType) {
            $conn->read(18); // IPv6 length + 2 bytes representing a port.
        } else {
            throw new PhpSocksException('Address type returned by the SOCKS5 server is invalid');
        }
    }
}
