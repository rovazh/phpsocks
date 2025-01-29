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
final class ConnectResponse implements Response
{
    private const VERSION = 0x05;
    private const NO_ACCEPTABLE_METHODS = 0xFF;

    private int $expectedAuthMethod;

    public function __construct(int $expectedAuthMethod)
    {
        $this->expectedAuthMethod = $expectedAuthMethod;
    }

    /**
     * {@inheritDoc}
     */
    public function receive(Stream $stream): void
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
        if ($this->expectedAuthMethod !== $method) {
            throw new PhpSocksException(
                sprintf('Unexpected auth method: expected "%d", got "%d"', $this->expectedAuthMethod, $method)
            );
        }
    }
}
