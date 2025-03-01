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

namespace PhpSocks;

use OutOfBoundsException;
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
    private int $size;
    private int $offset = 0;

    public function __construct(string $buf = '')
    {
        $this->buf = $buf;
        $this->size = strlen($buf);
    }

    /**
     * @throws PhpSocksException If the offset is out of buffer bounds.
     */
    public function readUInt8(): int
    {
        if ($this->offset >= $this->size) {
            throw new OutOfBoundsException('Offset exceeds buffer size.');
        }
        $char = $this->unpack($this->offset, self::UNSIGNED_CHAR);
        $this->offset++;
        return $char;
    }

    /**
     * @throws PhpSocksException If the offset is out of buffer bounds.
     */
    public function readUInt16(): int
    {
        if ($this->offset + 1 >= $this->size) {
            throw new OutOfBoundsException('Offset exceeds buffer size.');
        }
        $uShort = $this->unpack($this->offset, self::UNSIGNED_SHORT);
        $this->offset += 2;
        return $uShort;
    }

    public function readString(int $limit): string
    {
        $str = substr($this->buf, $this->offset, $limit);
        $this->offset += $limit;
        return $str;
    }

    /**
     * @throws InvalidArgumentException If the value is out of range.
     * @throws PhpSocksException If packing fails.
     */
    public function writeUInt8(int $char): self
    {
        if ($char < 0 || $char > 255) {
            throw new InvalidArgumentException('Value must be within the UInt8 range (0-255).');
        }
        $this->buf .= $this->pack($char, self::UNSIGNED_CHAR);
        $this->size++;
        return $this;
    }

    /**
     * @throws InvalidArgumentException If the value is out of range.
     * @throws PhpSocksException If packing fails.
     */
    public function writeUInt16(int $value): self
    {
        if ($value < 0 || $value > 65535) {
            throw new InvalidArgumentException('Value must be within the UInt16 range (0-65535).');
        }
        $this->buf .= $this->pack($value, self::UNSIGNED_SHORT);
        $this->size += 2;
        return $this;
    }

    public function writeString(string $value): self
    {
        $this->buf .= $value;
        $this->size += strlen($value);
        return $this;
    }

    public function flush(): string
    {
        $content = $this->buf;
        $this->buf = '';
        $this->size = 0;
        $this->offset = 0;
        return $content;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @throws PhpSocksException If packing fails.
     */
    private function pack(int $val, string $type): string
    {
        if (false === ($packedVal = @pack($type, $val))) {
            throw new PhpSocksException('Failed to pack the given value.');
        }
        return $packedVal;
    }

    /**
     * @throws PhpSocksException If unpacking fails.
     */
    private function unpack(int $offset, string $type): int
    {
        $unpacked = @unpack($type, $this->buf, $offset);
        if (($unpacked === false) || !is_int($val = $unpacked[1])) {
            throw new PhpSocksException('Failed to unpack the value at the given offset.');
        }
        return $val;
    }

    public function __toString(): string
    {
        return $this->flush();
    }
}
