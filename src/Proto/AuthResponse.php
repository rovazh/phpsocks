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

final class AuthResponse implements Response
{
    private const VERSION_OCTET_POSITION = 0;
    private const STATUS_OCTET_POSITION = 1;
    private const VERSION = 0x01;
    private const STATUS_SUCCESS = 0x00;
    private const BYTES_TO_READ = 2;

    /**
     * {@inheritDoc}
     */
    public function receive(Connection $conn): void
    {
        $buf = $conn->read(self::BYTES_TO_READ);
        $ver = $buf->readUInt8(self::VERSION_OCTET_POSITION);
        $status = $buf->readUInt8(self::STATUS_OCTET_POSITION);

        if (self::VERSION !== $ver) {
            throw new PhpSocksException(
                sprintf('Version is invalid: expected %d, got %d', self::VERSION, $ver)
            );
        }
        if (self::STATUS_SUCCESS !== $status) {
            throw new PhpSocksException('Authentication failed');
        }
    }
}
