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

use PhpSocks\Exception\PhpSocksException;

/**
 * Interface Stream
 *
 * Represents a stream, specifically a network connection to a destination address.
 * @psalm-api
 */
interface Stream
{
    /**
     * Reads the remainder of the stream into a string.
     *
     * @return string The read string.
     *
     * @throws PhpSocksException If an error occurs while reading from the stream.
     */
    public function readAll(): string;

    /**
     * Reads the stream into a string.
     *
     * @param int $length The maximum number of bytes to read.
     * @return string The read string.
     *
     * @throws PhpSocksException If an error occurs while reading from the stream.
     */
    public function read(int $length): string;

    /**
     * Writes a string to the stream.
     *
     * @param string $data The string to be written.
     * @return int The number of bytes written to the stream.

     * @throws PhpSocksException If an error occurs while writing to the stream.
     */
    public function write(string $data): int;

    /**
     * Closes the underlying stream. After the stream is closed, it cannot be read from or written to.
     *
     * @return void
     */
    public function close(): void;
}
