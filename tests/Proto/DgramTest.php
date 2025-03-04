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

use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use PhpSocks\Proto\Dgram;

final class DgramTest extends TestCase
{
    /**
     * @test
     */
    public function preparesRequestWithIPv4(): void
    {
        $this->assertEquals(
            "\x00\x00\x00\x01\x7f\x00\x00\x01\x00\x50Hello",
            (string)Dgram::prep('127.0.0.1', 80, 'Hello')
        );
    }

    /**
     * @test
     */
    public function preparesRequestWithIPv6(): void
    {
        $this->assertEquals(
            "\x00\x00\x00\x04\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01\x00\x50Hello",
            (string)Dgram::prep('::1', 80, 'Hello')
        );
    }

    /**
     * @test
     */
    public function preparesRequestWithDomainName(): void
    {
        $this->assertEquals(
            "\x00\x00\x00\x03\x09localhost\x00\x50Hello",
            (string)Dgram::prep('localhost', 80, 'Hello')
        );
    }
}
