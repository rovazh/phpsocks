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

/**
 * @internal
 */
final class Details
{
    private const VERSION = 0x05;
    private const RESERVED_OCTET = 0x00;
    private const ADDRESS_TYPE_DOMAIN_NAME = 0x03;
    private const ADDRESS_TYPE_IPV4 = 0x01;
    private const ADDRESS_TYPE_IPV6 = 0x04;
    private const SUCCESS = 0x00;
    private const MAX_REPL_SIZE = 261;

    public const CMD_CONNECT = 0x01;
    public const CMD_ASSOCIATE = 0x03;

    /**
     * @var array<int, string>
     */
    private static array $errors = [
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
     * @param self::CMD_CONNECT|self::CMD_ASSOCIATE $cmd
     *
     * @throws PhpSocksException
     */
    public static function send(Stream $stream, string $dstAddr, int $dstPort, int $cmd): void
    {
        $buf = new Buffer();
        $buf->writeUInt8(self::VERSION)
            ->writeUInt8($cmd)
            ->writeUInt8(self::RESERVED_OCTET);

        $packedDstAddr = @inet_pton($dstAddr);
        if (
            false === $packedDstAddr
            && false !== filter_var($dstAddr, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
        ) {
            $buf->writeUInt8(self::ADDRESS_TYPE_DOMAIN_NAME)
                ->writeUInt8(strlen($dstAddr))
                ->writeString($dstAddr);
        } elseif (filter_var($dstAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $buf->writeUInt8(self::ADDRESS_TYPE_IPV4)
                ->writeString($packedDstAddr);
        } elseif (filter_var($dstAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $buf->writeUInt8(self::ADDRESS_TYPE_IPV6)
                ->writeString($packedDstAddr);
        } else {
            throw new PhpSocksException('Invalid destination host ' . $dstAddr);
        }

        $buf->writeUInt16($dstPort);
        $stream->write((string)$buf);
    }

    /**
     * @return array{bnd_addr: string, bnd_port: int}
     *
     * @throws PhpSocksException
     */
    public static function receive(Stream $stream): array
    {
        $buf = new Buffer($stream->read(self::MAX_REPL_SIZE));

        $ver = $buf->readUInt8();
        $reply = $buf->readUInt8();

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
        if (self::RESERVED_OCTET !==  $buf->readUInt8()) {
            throw new PhpSocksException('Invalid reserved octet');
        }

        $result = [];

        $addrType = $buf->readUInt8();
        if (self::ADDRESS_TYPE_DOMAIN_NAME === $addrType) {
            $length = $buf->readUInt8(); // Domain name length
            $result['bnd_addr'] = $buf->readString($length);
            $result['bnd_port'] = $buf->readUint16();
        } elseif (self::ADDRESS_TYPE_IPV4 === $addrType) {
            $bndAddr = @inet_ntop($buf->readString(4));
            if (false === $bndAddr) {
                throw new PhpSocksException('Invalid BND.ADDR');
            }
            $result['bnd_addr'] = $bndAddr;
            $result['bnd_port'] = $buf->readUint16();
        } elseif (self::ADDRESS_TYPE_IPV6 === $addrType) {
            $bndAddr = @inet_ntop($buf->readString(16));
            if (false === $bndAddr) {
                throw new PhpSocksException('Invalid BND.ADDR');
            }
            $result['bnd_addr'] = $bndAddr;
            $result['bnd_port'] = $buf->readUint16();
        } else {
            throw new PhpSocksException('Address type returned by the SOCKS5 server is invalid');
        }

        return $result;
    }
}
