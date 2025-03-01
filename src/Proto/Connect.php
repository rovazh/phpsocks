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

namespace PhpSocks\Proto;

use PhpSocks\Buffer;
use PhpSocks\Exception\PhpSocksException;
use PhpSocks\Stream;

/**
 * @internal
 */
final class Connect
{
    private const VERSION = 0x05;
    private const NUMBER_OF_METHODS = 0x01;
    private const NO_ACCEPTABLE_METHODS = 0xFF;
    private const NO_AUTH = 0x00;

    /**
     * @throws PhpSocksException
     */
    public static function send(Stream $stream, int $authMethod = self::NO_AUTH): void
    {
        $buf = new Buffer();
        $buf
            ->writeUInt8(self::VERSION)
            ->writeUInt8(self::NUMBER_OF_METHODS)
            ->writeUInt8($authMethod);
        $stream->write((string)$buf);
    }

    /**
     * @throws PhpSocksException
     */
    public static function receive(Stream $stream, int $authMethod = self::NO_AUTH): void
    {
        $buf = new Buffer($stream->read(2));
        $ver = $buf->readUInt8();
        $method = $buf->readUInt8();

        if (self::VERSION !== $ver) {
            throw new PhpSocksException('Invalid version');
        }
        if (self::NO_ACCEPTABLE_METHODS === $method) {
            throw new PhpSocksException('No acceptable methods');
        }
        if ($authMethod !== $method) {
            throw new PhpSocksException(
                sprintf('Unexpected auth method: expected "%d", got "%d"', $authMethod, $method)
            );
        }
    }
}
