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

namespace PhpSocks\Tests;

use InvalidArgumentException;
use PhpSocks\Client;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ClientTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideInvalidConfig
     */
    public function doesNotAcceptInvalidConfig(array $config, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Client($config);
    }

    public static function provideInvalidConfig(): array
    {
        return [
            'missing both host and port' => [
                'config' => [],
                'expectMessage' => "Missing both 'host' and 'port' parameters.",
            ],
            'host is not a string' => [
                'config' => ['host' => 123, 'port' => 1080],
                'expectMessage' => "The 'host' parameter must be a string.",
            ],
            'port is not an integer' => [
                'config' => ['host' => '127.0.0.1', 'port' => 'not-an-int'],
                'expectMessage' => "The 'port' parameter must be an integer.",
            ],
            'connect_timeout is negative' => [
                'config' => ['host' => '127.0.0.1', 'port' => 1080, 'connect_timeout' => -5],
                'expectMessage' => "The 'connect_timeout' parameter must be a positive number.",
            ],
            'connect_timeout is not a number' => [
                'config' => ['host' => '127.0.0.1', 'port' => 1080, 'connect_timeout' => 'not-a-number'],
                'expectMessage' => "The 'connect_timeout' parameter must be a positive number.",
            ],
            'auth username and password are not strings' => [
                'config' => [
                    'host' => '127.0.0.1',
                    'port' => 1080,
                    'auth' => ['username' => 123, 'password' => []]
                ],
                'expectMessage' => "The 'username' and 'password' parameters must be strings.",
            ],
            'timeout is not positive' => [
                'config' => ['host' => '127.0.0.1', 'port' => 1080, 'timeout' => 0],
                'expectMessage' => "The 'timeout' parameter must be a positive integer.",
            ],
            'timeout is not an integer' => [
                'config' => ['host' => '127.0.0.1', 'port' => 1080, 'timeout' => 'not-an-int'],
                'expectMessage' => "The 'timeout' parameter must be a positive integer.",
            ],
        ];
    }

    /**
     * @test
     */
    public function acceptsValidConfig(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 1080,
            'timeout' => 10,
            'connect_timeout' => 1.5,
            'auth' => ['username' => 'user', 'password' => 'pass']
        ];
        $this->assertInstanceOf(Client::class, new Client($config));
    }

    /**
     * @test
     */
    public function acceptsMinimalValidConfig(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 1080,
        ];
        $this->assertInstanceOf(Client::class, new Client($config));
    }
}
