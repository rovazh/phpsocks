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

final class Dgram
{
    public const MAX_HEADER_LENGTH = 262;

    private const RESERVED_OCTET = 0x00;
    private const NO_FRAGMENT = 0x00;
    private const ADDRESS_TYPE_DOMAIN_NAME = 0x03;
    private const ADDRESS_TYPE_IPV4 = 0x01;
    private const ADDRESS_TYPE_IPV6 = 0x04;

    /**
     * @throws PhpSocksException
     */
    public static function prep(string $destHost, int $destPort, string $data): Buffer
    {
        $buf = new Buffer();
        $buf->writeUInt8(self::RESERVED_OCTET)
            ->writeUInt8(self::RESERVED_OCTET)
            ->writeUInt8(self::NO_FRAGMENT);

        $packedHost = @inet_pton($destHost);
        if (false === $packedHost && false !== filter_var($destHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $buf->writeUInt8(self::ADDRESS_TYPE_DOMAIN_NAME)
                ->writeUInt8(strlen($destHost))
                ->writeString($destHost);
        } elseif (filter_var($destHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $buf->writeUInt8(self::ADDRESS_TYPE_IPV4)
                ->writeString($packedHost);
        } elseif (filter_var($destHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $buf->writeUInt8(self::ADDRESS_TYPE_IPV6)
                ->writeString($packedHost);
        } else {
            throw new PhpSocksException('Invalid destination host ' . $destHost);
        }

        return $buf->writeUInt16($destPort)->writeString($data);
    }

    /**
     * @throws PhpSocksException
     */
    public static function parse(Buffer $buf, int $dataLength): string
    {
        if ($buf->readUInt8() !== self::RESERVED_OCTET || $buf->readUInt8() !== self::RESERVED_OCTET) {
            throw new PhpSocksException('Invalid reserved octet');
        }
        if ($buf->readUInt8() !== self::NO_FRAGMENT) {
            throw new PhpSocksException('Invalid fragment octet');
        }

        $addrType = $buf->readUInt8();
        if (self::ADDRESS_TYPE_DOMAIN_NAME === $addrType) {
            $addrLength = $buf->readUInt8();
            $buf->readString($addrLength);
            $buf->readUint16();
        } elseif (self::ADDRESS_TYPE_IPV4 === $addrType) {
            $buf->readString(4);
            $buf->readUint16();
        } elseif (self::ADDRESS_TYPE_IPV6 === $addrType) {
            $buf->readString(16);
            $buf->readUint16();
        } else {
            throw new PhpSocksException('Address type returned by the SOCKS5 server is invalid');
        }

        return $buf->readString($dataLength);
    }
}
