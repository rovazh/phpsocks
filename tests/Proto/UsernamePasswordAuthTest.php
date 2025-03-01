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

namespace PhpSocks\Tests\Proto;

use Mockery;
use PhpSocks\Exception\PhpSocksException;
use PhpSocks\Proto\UsernamePasswordAuth;
use PhpSocks\Stream;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;

final class UsernamePasswordAuthTest extends TestCase
{
    /**
     * @test
     */
    public function sendsRequest(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        UsernamePasswordAuth::send($stream, 'username', 'password');
        $stream
            ->shouldHaveReceived('write')
            ->once()
            ->with("\x01\x08username\x08password");
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
            ->andReturn("\x01\x00");
        UsernamePasswordAuth::receive($stream);
    }

    /**
     * @test
     */
    public function receivesAuthFailedResponse(): void
    {
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('UsernamePasswordAuth failed');

        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(2)
            ->andReturn("\x01\x09");

        UsernamePasswordAuth::receive($stream);
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
            ->andReturn("\x05\x00");

        UsernamePasswordAuth::receive($stream);
    }
}
