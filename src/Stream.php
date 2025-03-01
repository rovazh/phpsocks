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
     * Reads data from the stream.
     *
     * @param int $length The maximum number of bytes to read.
     * @return string The data read from the stream.
     *
     * @throws PhpSocksException If an error occurs while reading from the stream.
     */
    public function read(int $length): string;

    /**
     * Writes data to the stream.
     *
     * @param string $data The data to write.
     * @return int The number of bytes successfully written.
     *
     * @throws PhpSocksException If an error occurs while writing to the stream.
     */
    public function write(string $data): int;

    /**
     * Closes the underlying stream. Once closed, the stream can no longer be read from or written to.
     *
     * @return void
     */
    public function close(): void;
}
