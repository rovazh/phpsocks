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
    private int $size;
    private int $offset = 0;

    public function __construct(string $buf = '')
    {
        $this->buf = $buf;
        $this->size = strlen($buf);
    }

    /**
     * @throws PhpSocksException when the index is out of range.
     */
    public function readUInt8(): int
    {
        $char = $this->unpack($this->offset, self::UNSIGNED_CHAR);
        $this->offset++;
        return $char;
    }

    /**
     * @throws PhpSocksException when the index is out of range.
     */
    public function readUint16(): int
    {
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
     * @throws InvalidArgumentException|PhpSocksException
     */
    public function writeUInt8(int $char): self
    {
        if ($char < 0 || $char > 255) {
            throw new InvalidArgumentException('Value exceeds uint8 range (0-255)');
        }
        $this->buf .= $this->pack($char, self::UNSIGNED_CHAR);
        $this->size++;
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
        $this->size += 2;
        return $this;
    }

    public function writeString(string $value): self
    {
        $this->buf .= $value;
        $len = strlen($value);
        $this->size += $len;
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
