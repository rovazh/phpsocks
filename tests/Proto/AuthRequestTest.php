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
use PhpSocks\Proto\AuthRequest;
use PhpSocks\Stream;

final class AuthRequestTest extends TestCase
{
    /**
     * @test
     */
    public function sendsRequest(): void
    {
        $stream = Mockery::spy(Stream::class)->makePartial();
        $req = new AuthRequest(new Buffer(), 'username', 'pass');
        $req->send($stream);
        $stream
            ->shouldHaveReceived('write')
            ->with(pack('C2', 0x01, 8) . 'username' . pack('C', 4) . 'pass')
            ->once();
    }
}
