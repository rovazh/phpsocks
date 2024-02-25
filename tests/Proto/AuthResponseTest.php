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
use PhpSocks\Proto\AuthResponse;
use PhpSocks\Stream;

/**
 * @internal
 */
final class AuthResponseTest extends TestCase
{
    /**
     * @test
     */
    public function receivesSuccess(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->expects('read')->with(2)->andReturn(pack('C2', 0x01, 0x00));
        $res = new AuthResponse();
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInvalidVersionReturned(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->expects('read')->with(2)->andReturn(pack('C2', 0x07, 0x00));
        $res = new AuthResponse();
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Invalid version');
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenAuthFailed(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->expects('read')->with(2)->andReturn(pack('C2', 0x01, 0xFF));
        $res = new AuthResponse();
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Authentication failed');
        $res->receive($stream);
    }
}
