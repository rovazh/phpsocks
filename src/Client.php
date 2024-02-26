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
use PhpSocks\Proto\ConnectRequest;
use PhpSocks\Proto\ConnectResponse;
use PhpSocks\Proto\DetailsRequest;
use PhpSocks\Proto\DetailsResponse;
use PhpSocks\Proto\AuthRequest;
use PhpSocks\Proto\AuthResponse;

/**
 * Class Client
 *
 * Represents a client responsible for establishing a connection to the destination address through a SOCKS5 server.
 * @psalm-api
 */
class Client
{
    private const USERNAME_PASSWORD = 0x02;
    private const NO_AUTH = 0x00;

    private string $host;
    private int $port;
    private float $connectTimeout = 0;
    private int $authMethod = self::NO_AUTH;
    /**
     * @var array{username: string, password: string}
     */
    private array $auth = ['username' => '', 'password' => ''];
    private int $timeout = 0;

    /**
     * Initializes a new instance of the Client class with the provided configuration.
     *
     * @param array $config An array of configuration for setting up the client.
     *
     * @throws InvalidArgumentException If the configuration provided is invalid.
     */
    public function __construct(array $config)
    {
        if (!isset($config['host'], $config['port'])) {
            throw new InvalidArgumentException('missing host and port');
        }
        if (!is_string($config['host'])) {
            throw new InvalidArgumentException('host must be a string');
        }
        if (!is_int($config['port'])) {
            throw new InvalidArgumentException('port must be an integer');
        }
        if (isset($config['connect_timeout'])) {
            if (
                (is_float($config['connect_timeout']) || is_int($config['connect_timeout']))
                && $config['connect_timeout'] > 0
            ) {
                $this->connectTimeout = (float)$config['connect_timeout'];
            } else {
                throw new InvalidArgumentException('connect_timeout must be a positive number');
            }
        }
        if (isset($config['auth']['username'], $config['auth']['password'])) {
            if (!is_string($config['auth']['username']) || !is_string($config['auth']['password'])) {
                throw new InvalidArgumentException('username and password must be strings');
            }
            $this->authMethod = self::USERNAME_PASSWORD;
            $this->auth['username'] = $config['auth']['username'];
            $this->auth['password'] = $config['auth']['password'];
        }

        $this->host = $config['host'];
        $this->port = $config['port'];

        if (isset($config['timeout'])) {
            if (is_int($config['timeout']) && $config['timeout'] > 0) {
                $this->timeout = $config['timeout'];
            } else {
                throw new InvalidArgumentException('timeout must be a positive integer');
            }
        }
    }

    /**
     * Connects to the destination address provided in the URI through a SOCKS5 proxy.
     *
     * @param string $uri The URI to connect to.
     * @param array{tls?: array<string, string|bool|int|array>} $options TLS options for the connection.
     * @return Stream A stream representing the connection to the destination address.
     *
     * @throws PhpSocksException If an error occurs during the connection to the SOCKS5 server or the destination URI.
     * @throws InvalidArgumentException If the URI or options are invalid.
     */
    public function connect(string $uri, array $options = []): Stream
    {
        $uriParts = parse_url($uri);
        if (false === $uriParts) {
            throw new InvalidArgumentException('Invalid destination URI');
        }
        if (!isset($uriParts['scheme'])) {
            throw new InvalidArgumentException('Invalid destination URI: no scheme');
        }
        if (!($uriParts['scheme'] === 'tcp' || $uriParts['scheme'] === 'tls')) {
            throw new InvalidArgumentException('Invalid destination URI: unexpected scheme');
        }
        if (!isset($uriParts['host'])) {
            throw new InvalidArgumentException('Invalid destination URI: no host');
        }
        if (!isset($uriParts['port'])) {
            throw new InvalidArgumentException('Invalid destination URI: no port');
        }

        $stream = $this->connectToSocks();

        $connReq = new ConnectRequest(new Buffer(), $this->authMethod);
        $connReq->send($stream);
        $connRes = new ConnectResponse($this->authMethod);
        $connRes->receive($stream);

        if (self::USERNAME_PASSWORD === $this->authMethod) {
            $authReq = new AuthRequest(new Buffer(), $this->auth['username'], $this->auth['password']);
            $authReq->send($stream);
            $authRes = new AuthResponse();
            $authRes->receive($stream);
        }

        $detailsReq = new DetailsRequest(new Buffer(), $uriParts['host'], $uriParts['port']);
        $detailsReq->send($stream);
        $detailsRes = new DetailsResponse();
        $detailsRes->receive($stream);

        if ($uriParts['scheme'] === 'tls') {
            $stream->enableEncryption($options['tls'] ?? []);
        }

        return $stream;
    }

    /**
     * @throws PhpSocksException
     */
    private function connectToSocks(): TCPSocketStream
    {
        $addr = 'tcp://' . $this->host . ':' . $this->port;
        if ($this->connectTimeout) {
            $sock = @stream_socket_client($addr, $_, $err, $this->connectTimeout);
        } else {
            $sock = @stream_socket_client($addr, $_, $err);
        }
        if (!$sock) {
            throw new PhpSocksException('Failed to connect to the SOCKS server: ' . $err);
        }
        if ($this->timeout) {
            if (!@stream_set_timeout($sock, $this->timeout)) {
                throw new PhpSocksException('Failed to set timeout period on the stream');
            }
        }
        return new TCPSocketStream($sock);
    }
}
