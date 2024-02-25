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

namespace PhpSocks;

use PhpSocks\Exception\InvalidArgumentException;
use PhpSocks\Exception\PhpSocksException;

/**
 * @internal
 */
class Buffer
{
    private const UNSIGNED_CHAR = 'C';
    private const UNSIGNED_SHORT = 'n';

    private string $buf;

    public function __construct(string $buf = '')
    {
        $this->buf = $buf;
    }

    /**
     * @throws PhpSocksException when the index is out of range
     */
    public function readUInt8(int $offset): int
    {
        return $this->unpack($offset, self::UNSIGNED_CHAR);
    }

    /**
     * @throws InvalidArgumentException|PhpSocksException
     */
    public function writeUInt8(int $char): self
    {
        if ($char < 0 || $char > 255) {
            throw new InvalidArgumentException('Value exceeds uint8 range (0-255)');
        }
        $this->buf .= $this->pack($char, self::UNSIGNED_CHAR);
        return $this;
    }

    /**
     * @throws InvalidArgumentException|PhpSocksException
     */
    public function writeUInt16(int $value): self
    {
        if ($value < 0 || $value > 65535) {
            throw new InvalidArgumentException('Value exceeds uint16 range (0-65535)');
        }
        $this->buf .= $this->pack($value, self::UNSIGNED_SHORT);
        return $this;
    }

    public function writeString(string $value): self
    {
        $this->buf .= $value;
        return $this;
    }

    public function flush(): string
    {
        $content = $this->buf;
        $this->buf = '';
        return $content;
    }

    /**
     * @throws PhpSocksException
     */
    private function pack(int $val, string $type): string
    {
        if (false === ($packedVal = @pack($type, $val))) {
            throw new PhpSocksException('Failed to pack value');
        }
        return $packedVal;
    }

    /**
     * @throws PhpSocksException
     */
    private function unpack(int $offset, string $type): int
    {
        $unpacked = @unpack($type, $this->buf, $offset);
        if (($unpacked === false) || !is_int($byte = $unpacked[1])) {
            throw new PhpSocksException('Offset is out of buffer bounds');
        }
        return $byte;
    }
}
