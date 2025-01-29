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

final class AuthResponse implements Response
{
    private const VERSION = 0x01;
    private const STATUS_SUCCESS = 0x00;

    /**
     * {@inheritDoc}
     */
    public function receive(Stream $stream): void
    {
        $buf = new Buffer($stream->read(2));
        $ver = $buf->readUInt8();
        $status = $buf->readUInt8();

        if (self::VERSION !== $ver) {
            throw new PhpSocksException('Invalid version');
        }
        if (self::STATUS_SUCCESS !== $status) {
            throw new PhpSocksException('Authentication failed');
        }
    }
}
