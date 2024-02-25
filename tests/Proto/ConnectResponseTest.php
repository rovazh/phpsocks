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
use PhpSocks\Proto\ConnectResponse;
use PhpSocks\Stream;

/**
 * @internal
 */
final class ConnectResponseTest extends TestCase
{
    /**
     * @test
     */
    public function receivesSuccess(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->expects('read')->with(2)->andReturn(pack('C2', 0x05, 0x02))->once();
        $res = new ConnectResponse(0x02);
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInvalidVersionReturned(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->expects('read')->with(2)->andReturn(pack('C2', 0x01, 0x02))->once();
        $res = new ConnectResponse(0x02);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Invalid version');
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenNoAcceptableMethodsReturned(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->expects('read')->with(2)->andReturn(pack('C2', 0x05, 0xFF))->once();
        $res = new ConnectResponse(0x02);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('No acceptable methods');
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenUnexpectedAuthMethodReturned(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->expects('read')->with(2)->andReturn(pack('C2', 0x05, 0x03))->once();
        $res = new ConnectResponse(0x02);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Unexpected auth method: expected "2", got "3"');
        $res->receive($stream);
    }
}
