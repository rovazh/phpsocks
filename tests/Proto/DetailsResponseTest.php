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
use PhpSocks\Proto\DetailsResponse;
use PhpSocks\Stream;

final class DetailsResponseTest extends TestCase
{
    /**
     * @test
     */
    public function receivesSuccessWithIPv4(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream
            ->expects('read')
            ->with(4)
            ->andReturn(pack('C4', 0x05, 0x00, 0x00, 0x01))
            ->once()
            ->ordered();
        $stream
            ->expects('read')
            ->with(6)
            ->once()
            ->ordered();
        $res = new DetailsResponse();
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function receivesSuccessWithIPv6(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream
            ->expects('read')
            ->with(4)
            ->andReturn(pack('C4', 0x05, 0x00, 0x00, 0x04))
            ->once()
            ->ordered();
        $stream
            ->expects('read')
            ->with(18)
            ->once()
            ->ordered();
        $res = new DetailsResponse();
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function receivesSuccessWithDomainName(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream
            ->expects('read')
            ->with(4)
            ->andReturn(pack('C4', 0x05, 0x00, 0x00, 0x03))
            ->once()
            ->ordered();
        $stream
            ->expects('read')
            ->with(1)
            ->andReturn(pack('C', 9))
            ->once()
            ->ordered();
        $stream
            ->expects('read')
            ->with(11)
            ->once()
            ->ordered();
        $res = new DetailsResponse();
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInvalidAddressTypeReturned(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream
            ->expects('read')
            ->with(4)
            ->andReturn(pack('C4', 0x05, 0x00, 0x00, 0xFF))
            ->once()
            ->ordered();
        $res = new DetailsResponse();
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Address type returned by the SOCKS5 server is invalid');
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInvalidVersionReturned(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream
            ->expects('read')
            ->with(4)
            ->andReturn(pack('C4', 0xFF, 0x00, 0x00, 0x01))
            ->once()
            ->ordered();
        $res = new DetailsResponse();
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Invalid version');
        $res->receive($stream);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInvalidReservedOctetReturned(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream
            ->expects('read')
            ->with(4)
            ->andReturn(pack('C4', 0x05, 0x00, 0xFF, 0x01))
            ->once()
            ->ordered();
        $res = new DetailsResponse();
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Invalid reserved octet');
        $res->receive($stream);
    }

    /**
     * @test
     * @dataProvider provideDataForSocksErrorTest
     */
    public function throwsExceptionOnSocksError(int $reply, $errMessage): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream
            ->expects('read')
            ->with(4)
            ->andReturn(pack('C4', 0x05, $reply, 0x00, 0x01))
            ->once()
            ->ordered();
        $res = new DetailsResponse();
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage($errMessage);
        $res->receive($stream);
    }

    public static function  provideDataForSocksErrorTest(): iterable
    {
        return [
            'General SOCKS server failure' => [
                'reply' => 0x01,
                'error message' => 'General SOCKS server failure',
            ],
            'Connection not allowed by ruleset' => [
                'reply' => 0x02,
                'error message' => 'Connection not allowed by ruleset',
            ],
            'Network unreachable' => [
                'reply' => 0x03,
                'error message' => 'Network unreachable',
            ],
            'Host unreachable' => [
                'reply' => 0x04,
                'error message' => 'Host unreachable',
            ],
            'Connection refused' => [
                'reply' => 0x05,
                'error message' => 'Connection refused',
            ],
            'TTL expired' => [
                'reply' => 0x06,
                'error message' => 'TTL expired',
            ],
            'Command not supported' => [
                'reply' => 0x07,
                'error message' => 'Command not supported',
            ],
            'Address type not supported' => [
                'reply' => 0x08,
                'error message' => 'Address type not supported',
            ],
            'Unknown error' => [
                'reply' => 0xFF,
                'error message' => 'General SOCKS server failure',
            ],
        ];
    }
}
