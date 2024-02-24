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
use PhpSocks\Stream;

/**
 * @internal
 */
final class ConnectRequest implements Request
{
    private const VERSION = 0x05;
    private const NUMBER_OF_METHODS = 0x01;

    private Buffer $buf;
    private int $authMethod;

    public function __construct(Buffer $buf, int $authMethod)
    {
        $this->buf = $buf;
        $this->authMethod = $authMethod;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Stream $stream): void
    {
        $this->buf
            ->writeUInt8(self::VERSION)
            ->writeUInt8(self::NUMBER_OF_METHODS)
            ->writeUInt8($this->authMethod);
        $stream->write($this->buf->flush());
    }
}
