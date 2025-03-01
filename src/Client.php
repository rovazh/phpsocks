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

namespace PhpSocks;

use PhpSocks\Exception\InvalidArgumentException;
use PhpSocks\Exception\PhpSocksException;
use PhpSocks\Proto\Connect;
use PhpSocks\Proto\Details;
use PhpSocks\Proto\UsernamePasswordAuth;

/**
 * Class Client
 *
 * Handles establishing a connection to a destination address via a SOCKS5 proxy server.
 * @psalm-api
 */
class Client
{
    private string $host;
    private int $port;
    private float $connectTimeout = 0;

    /**
     * @var array{username: string, password: string}
     */
    private array $auth = ['username' => '', 'password' => ''];
    private int $timeout = 0;

    /**
     * Initializes a new instance of the Client class with the provided configuration.
     *
     * @param array $config An array of configuration options for setting up the client.
     *
     * @throws InvalidArgumentException If the configuration provided is invalid.
     */
    public function __construct(array $config)
    {
        if (!isset($config['host'], $config['port'])) {
            throw new InvalidArgumentException("Missing both 'host' and 'port' parameters.");
        }
        if (!is_string($config['host'])) {
            throw new InvalidArgumentException("The 'host' parameter must be a string.");
        }
        if (!is_int($config['port'])) {
            throw new InvalidArgumentException("The 'port' parameter must be an integer.");
        }
        if (isset($config['connect_timeout'])) {
            if (
                (is_float($config['connect_timeout']) || is_int($config['connect_timeout']))
                && $config['connect_timeout'] > 0
            ) {
                $this->connectTimeout = (float)$config['connect_timeout'];
            } else {
                throw new InvalidArgumentException("The 'connect_timeout' parameter must be a positive number.");
            }
        }
        if (isset($config['auth']['username'], $config['auth']['password'])) {
            if (!is_string($config['auth']['username']) || !is_string($config['auth']['password'])) {
                throw new InvalidArgumentException("The 'username' and 'password' parameters must be strings.");
            }
            $this->auth['username'] = $config['auth']['username'];
            $this->auth['password'] = $config['auth']['password'];
        }

        $this->host = $config['host'];
        $this->port = $config['port'];

        if (isset($config['timeout'])) {
            if (is_int($config['timeout']) && $config['timeout'] > 0) {
                $this->timeout = $config['timeout'];
            } else {
                throw new InvalidArgumentException("The 'timeout' parameter must be a positive integer.");
            }
        }
    }

    /**
     * Connects to the destination address provided in the URI through a SOCKS5 proxy.
     *
     * @param string $uri The URI to connect to.
     * @param array{tls?: array<string, string|bool|int|array>} $options TLS options for the connection.
     * @return Stream The established connection stream.
     *
     * @throws PhpSocksException If an error occurs while connecting to the SOCKS5 server or the destination URI.
     * @throws InvalidArgumentException If the URI or options are invalid.
     */
    public function connect(string $uri, array $options = []): Stream
    {
        $uriParts = $this->parseUri($uri, ['tcp', 'tls']);

        $stream = TcpStream::create($this->host, $this->port, [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ]);

        if ($this->auth['username'] && $this->auth['password']) {
            Connect::send($stream, UsernamePasswordAuth::METHOD);
            Connect::receive($stream, UsernamePasswordAuth::METHOD);
            UsernamePasswordAuth::send($stream, $this->auth['username'], $this->auth['password']);
            UsernamePasswordAuth::receive($stream);
        } else {
            Connect::send($stream);
            Connect::receive($stream);
        }
        Details::send($stream, $uriParts['host'], $uriParts['port'], Details::CMD_CONNECT);
        Details::receive($stream);

        if ($uriParts['scheme'] === 'tls') {
            $stream->enableEncryption($options['tls'] ?? []);
        }

        return $stream;
    }

    /**
     * Establishes a SOCKS5 UDP association to enable the relay of UDP datagrams to the destination address.
     *
     * @param string $uri The URI of the destination to associate with.
     * @return Stream The established UDP connection stream.
     *
     * @throws PhpSocksException If an error occurs while connecting to the SOCKS5 server or the destination URI.
     * @throws InvalidArgumentException If the URI or options are invalid.
     */
    public function associate(string $uri): Stream
    {
        $uriParts = $this->parseUri($uri, ['udp']);

        $stream = TcpStream::create($this->host, $this->port, [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ]);

        if ($this->auth['username'] && $this->auth['password']) {
            Connect::send($stream, UsernamePasswordAuth::METHOD);
            Connect::receive($stream, UsernamePasswordAuth::METHOD);
            UsernamePasswordAuth::send($stream, $this->auth['username'], $this->auth['password']);
            UsernamePasswordAuth::receive($stream);
        } else {
            Connect::send($stream);
            Connect::receive($stream);
        }
        Details::send($stream, '0.0.0.0', 0, Details::CMD_ASSOCIATE);
        $result = Details::receive($stream);

        return DgramStream::create(
            $stream,
            $result['bnd_addr'],
            $result['bnd_port'],
            $uriParts['host'],
            $uriParts['port'],
            ['timeout' => $this->timeout, 'connect_timeout' => $this->connectTimeout],
        );
    }

    /**
     * @return array{fragment?: string, host: string, pass?: string, path?: string, port: int, query?: string, scheme: string, user?: string}
     *
     * @throws InvalidArgumentException
     */
    private function parseUri(string $uri, array $schemes): array
    {
        $uriParts = parse_url($uri);
        if (false === $uriParts) {
            throw new InvalidArgumentException('Invalid destination URI');
        }
        if (!isset($uriParts['scheme'])) {
            throw new InvalidArgumentException('Invalid destination URI: no scheme');
        }
        if (!in_array($uriParts['scheme'], $schemes, true)) {
            throw new InvalidArgumentException('Invalid destination URI: unexpected scheme');
        }
        if (!isset($uriParts['host'])) {
            throw new InvalidArgumentException('Invalid destination URI: no host');
        }
        if (!isset($uriParts['port'])) {
            throw new InvalidArgumentException('Invalid destination URI: no port');
        }
        return $uriParts;
    }
}
