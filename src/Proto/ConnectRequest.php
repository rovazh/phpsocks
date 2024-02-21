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
use PhpSocks\Connection;

/**
 * @internal
 */
final class ConnectRequest implements Request
{
    private const VERSION = 0x05;
    private const NUMBER_OF_METHODS = 0x01;
    private const NO_AUTH = 0x00;
    private const USERNAME_PASSWORD_AUTH = 0x02;

    private Buffer $buf;
    private int $authMethod;

    /**
     * @param self::NO_AUTH|self::USERNAME_PASSWORD_AUTH $authMethod the authentication method.
     */
    public function __construct(Buffer $buf, int $authMethod)
    {
        $this->buf = $buf;
        $this->authMethod = $authMethod;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Connection $conn): void
    {
        $this->buf
            ->writeUInt8(self::VERSION)
            ->writeUInt8(self::NUMBER_OF_METHODS)
            ->writeUInt8($this->authMethod);
        $conn->write($this->buf);
    }
}
