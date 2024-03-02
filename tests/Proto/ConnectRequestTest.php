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
use PhpSocks\Proto\ConnectRequest;
use PhpSocks\Stream;

final class ConnectRequestTest extends TestCase
{
    /**
     * @test
     */
    public function sendsRequest(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        $req = new ConnectRequest(new Buffer(), 0x02);
        $req->send($stream);
        $stream
            ->shouldHaveReceived('write')
            ->with(pack('C3', 0x05, 0x01, 0x02))
            ->once();
    }
}
