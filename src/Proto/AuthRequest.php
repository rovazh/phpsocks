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
final class AuthRequest implements Request
{
    private const VERSION = 0x01;

    private Buffer $buf;
    private string $username;
    private string $password;

    public function __construct(Buffer $buf, string $username, string $password)
    {
        $this->buf = $buf;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Stream $stream): void
    {
        $this->buf->writeUInt8(self::VERSION)
            ->writeUInt8(strlen($this->username))
            ->writeString($this->username)
            ->writeUInt8(strlen($this->password))
            ->writeString($this->password);
        $stream->write($this->buf->flush());
    }
}
