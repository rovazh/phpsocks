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
     * @throws PhpSocksException If a connection error occurs with the SOCKS5 server or the destination URI.
     * @throws InvalidArgumentException If the URI or options are invalid.
     */
    public function connect(string $uri, array $options = []): Stream
    {
        $uriParts = $this->parseUri($uri, ['tcp', 'tls']);

        $stream = TcpStream::create($this->host, $this->port, [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ]);

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

        $detailsReq = new DetailsRequest(new Buffer(), $uriParts['host'], $uriParts['port'], DetailsRequest::CMD_CONNECT);
        $detailsReq->send($stream);
        $detailsRes = new DetailsResponse();
        $detailsRes->receive($stream);

        if ($uriParts['scheme'] === 'tls') {
            $stream->enableEncryption($options['tls'] ?? []);
        }

        return $stream;
    }

    /**
     * Establishes a SOCKS5 UDP association to enable the relay of UDP datagrams to the destination address.
     *
     * @param string $uri The URI of the destination to associate with.
     * @return Stream A stream representing the UDP connection to the destination address.
     *
     * @throws PhpSocksException If a connection error occurs with the SOCKS5 server or the destination URI.
     * @throws InvalidArgumentException If the URI or options are invalid.
     */
    public function associate(string $uri): Stream
    {
        $uriParts = $this->parseUri($uri, ['udp']);

        $socksStream = TcpStream::create($this->host, $this->port, [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ]);

        $connReq = new ConnectRequest(new Buffer(), $this->authMethod);
        $connReq->send($socksStream);
        $connRes = new ConnectResponse($this->authMethod);
        $connRes->receive($socksStream);

        $detailsReq = new DetailsRequest(new Buffer(), '0.0.0.0', 0, DetailsRequest::CMD_ASSOCIATE);
        $detailsReq->send($socksStream);
        $detailsRes = new DetailsResponse();
        $detailsRes->receive($socksStream);

        return DgramStream::create(
            $socksStream,
            $detailsRes->getBndAddr(),
            $detailsRes->getBndPort(),
            $uriParts['host'],
            $uriParts['port'],
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
