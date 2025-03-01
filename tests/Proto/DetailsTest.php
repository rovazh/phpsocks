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
use PhpSocks\Proto\Details;
use PhpSocks\Stream;

final class DetailsTest extends TestCase
{
    /**
     * @test
     */
    public function sendsRequestWithIPv4(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        Details::send($stream, '127.0.0.1', 80, Details::CMD_CONNECT);
        $stream
            ->shouldHaveReceived('write')
            ->once()
            ->with("\x05\x01\x00\x01\x7f\x00\x00\x01\x00\x50");
    }

    /**
     * @test
     */
    public function sendsRequestWithIPv6(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        Details::send($stream, '::1', 80, Details::CMD_CONNECT);
        $stream
            ->shouldHaveReceived('write')
            ->once()
            ->with("\x05\x01\x00\x04\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01\x00\x50");
    }

    /**
     * @test
     */
    public function sendsRequestWithDomainName(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        Details::send($stream, 'localhost', 80, Details::CMD_CONNECT);
        $stream
            ->shouldHaveReceived('write')
            ->once()
            ->with("\x05\x01\x00\x03\x09localhost\x00\x50");
    }

    /**
     * @test
     */
    public function throwsExceptionWhenSendingRequestWithInvalidDestAddr()
    {
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage('Invalid destination host !!3##2');

        $stream = Mockery::spy(Stream::class)->makePartial();
        Details::send($stream, '!!3##2', 80, Details::CMD_CONNECT);
        $stream
            ->shouldHaveReceived('write')
            ->never();
    }

    /**
     * @test
     */
    public function receivesSuccessfulResponseWithIPv4(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(261)
            ->andReturn("\x05\x00\x00\x01\x7f\x00\x00\x01\x00\x50");

        $result = Details::receive($stream);
        $this->assertEquals(['bnd_addr' => '127.0.0.1', 'bnd_port' => 80], $result);
    }

    /**
     * @test
     */
    public function receivesSuccessfulResponseWithIPv6(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(261)
            ->andReturn("\x05\x00\x00\x04\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01\x00\x50");

        $result = Details::receive($stream);
        $this->assertEquals(['bnd_addr' => '::1', 'bnd_port' => 80], $result);
    }

    /**
     * @test
     */
    public function receivesSuccessfulResponseWithDomainName(): void
    {
        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(261)
            ->andReturn("\x05\x00\x00\x03\x09localhost\x00\x50");

        $result = Details::receive($stream);
        $this->assertEquals(['bnd_addr' => 'localhost', 'bnd_port' => 80], $result);
    }

    /**
     * @test
     *
     * @dataProvider provideUnsuccessfulRelies
     */
    public function receivesResponseWithNoSuccess(string $reply, string $expectMessage): void
    {
        $this->expectException(PhpSocksException::class);
        $this->expectExceptionMessage($expectMessage);

        $stream = Mockery::mock(Stream::class)->makePartial();
        $stream->shouldReceive('read')
            ->once()
            ->with(261)
            ->andReturn("\x05" . $reply);

        Details::receive($stream);
    }

    public static function provideUnsuccessfulRelies(): array
    {
        return [
            'general SOCKS server failure' => [
                'reply' => "\x01",
                'expectMessage' => 'General SOCKS server failure',
            ],
            'connection not allowed by ruleset' => [
                'reply' => "\x02",
                'expectMessage' => 'Connection not allowed by ruleset',
            ],
            'Network unreachable' => [
                'reply' => "\x03",
                'expectMessage' => 'Network unreachable',
            ],
            'Host unreachable' => [
                'reply' => "\x04",
                'expectMessage' => 'Host unreachable',
            ],
            'Connection refused' => [
                'reply' => "\x05",
                'expectMessage' => 'Connection refused',
            ],
            'TTL expired' => [
                'reply' => "\x06",
                'expectMessage' => 'TTL expired',
            ],
            'Command not supported' => [
                'reply' => "\x07",
                'expectMessage' => 'Command not supported',
            ],
            'Address type not supported' => [
                'reply' => "\x08",
                'expectMessage' => 'Address type not supported',
            ],
            'general SOCKS server failure (custom reply)' => [
                'reply' => "\x09",
                'expectMessage' => 'General SOCKS server failure',
            ],
        ];
    }
}
