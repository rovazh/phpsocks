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
final class UsernamePasswordAuth
{
    private const VERSION = 0x01;
    private const STATUS_SUCCESS = 0x00;

    public const METHOD = 0x02;

    /**
     * @throws PhpSocksException
     */
    public static function send(Stream $stream, string $username, string $password): void
    {
        $buf = new Buffer();
        $buf->writeUInt8(self::VERSION)
            ->writeUInt8(strlen($username))
            ->writeString($username)
            ->writeUInt8(strlen($password))
            ->writeString($password);
        $stream->write((string)$buf);
    }

    /**
     * @throws PhpSocksException
     */
    public static function receive(Stream $stream): void
    {
        $buf = new Buffer($stream->read(2));
        $ver = $buf->readUInt8();
        $status = $buf->readUInt8();

        if (self::VERSION !== $ver) {
            throw new PhpSocksException('Invalid version');
        }
        if (self::STATUS_SUCCESS !== $status) {
            throw new PhpSocksException('UsernamePasswordAuth failed');
        }
    }
}
