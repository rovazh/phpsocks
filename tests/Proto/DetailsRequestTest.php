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
use PhpSocks\Buffer;
use PhpSocks\Exception\PhpSocksException;
use PhpSocks\Proto\DetailsRequest;
use PhpSocks\Stream;

/**
 * @internal
 */
final class DetailsRequestTest extends TestCase
{
    /**
     * @test
     */
    public function sendsConnectRequestWithIPv4(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        $req = new DetailsRequest(new Buffer(), '127.0.0.1', 80);
        $req->send($stream);
        $stream
            ->shouldHaveReceived('write')
            ->with(pack('C4', 0x05, 0x01, 0x00, 0x01) . inet_pton('127.0.0.1') . pack('n', 80))
            ->once();
    }

    /**
     * @test
     */
    public function sendsConnectRequestWithIPv6(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        $req = new DetailsRequest(new Buffer(), '::1', 80);
        $req->send($stream);
        $stream
            ->shouldHaveReceived('write')
            ->with(pack('C4', 0x05, 0x01, 0x00, 0x04) . inet_pton('::1') . pack('n', 80))
            ->once();
    }

    /**
     * @test
     */
    public function sendsConnectRequestWithDomainName(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        $req = new DetailsRequest(new Buffer(), 'localhost', 80);
        $req->send($stream);
        $stream
            ->shouldHaveReceived('write')
            ->with(pack('C5', 0x05, 0x01, 0x00, 0x03, 9) . 'localhost' . pack('n', 80))
            ->once();
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInvalidAddressProvided(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $req = new DetailsRequest(new Buffer(), '$$$', 80);
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Invalid destination host $$$');
        $req->send($stream);
    }
}
