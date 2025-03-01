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

namespace PhpSocks\Tests\Proto;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use PhpSocks\Exception\PhpSocksException;
use PhpSocks\Proto\Connect;
use PhpSocks\Stream;

final class ConnectTest extends TestCase
{
    /**
     * @test
     */
    public function sendsRequest(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        Connect::send($stream);
        $stream
            ->shouldHaveReceived('write')
            ->once()
            ->with("\x05\x01\x00");
    }

    /**
     * @test
     */
    public function receivesSuccessfulResponse(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(2)
            ->andReturn("\x05\x00");
        Connect::receive($stream);
    }

    /**
     * @test
     */
    public function receivesResponseWithInvalidVersion(): void
    {
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Invalid version');

        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(2)
            ->andReturn("\x03\x00");

        Connect::receive($stream);
    }

    /**
     * @test
     */
    public function receivesNoAcceptableMethodsResponse(): void
    {
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('No acceptable methods');

        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(2)
            ->andReturn("\x05\xFF");

        Connect::receive($stream);
    }

    /**
     * @test
     */
    public function receivesResponseWithUnexpectedAuthMethod(): void
    {
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Unexpected auth method: expected "0", got "2"');

        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(2)
            ->andReturn("\x05\x02");

        Connect::receive($stream);
    }
}
