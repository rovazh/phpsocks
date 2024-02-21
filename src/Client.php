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

use InvalidArgumentException;
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
    /**
     * @var array{host: string, port: int, auth?: array{username: string, password: string}}
     */
    private array $options;
    /**
     * @var array{username: string, password: string}|array
     */
    private array $auth;

    /**
     * Initializes a new instance of the Client class with the provided options.
     *
     * @param array $options An array of options for configuring the client.
     *
     * @throws InvalidArgumentException If the options provided are invalid.
     */
    public function __construct(array $options)
    {
        if (!isset($options['host'], $options['port'])) {
            throw new InvalidArgumentException('Missing host and port');
        }
        if (!is_string($options['host'])) {
            throw new InvalidArgumentException('Host must be a string');
        }
        if (!is_int($options['port'])) {
            throw new InvalidArgumentException('Port must be an integer');
        }

        if (isset($options['auth']['username'], $options['auth']['password'])) {
            if (!is_string($options['auth']['username']) || !is_string($options['auth']['password'])) {
                throw new InvalidArgumentException('Username and password must be strings');
            }
            $this->auth['username'] = $options['auth']['username'];
            $this->auth['password'] = $options['auth']['password'];
        }

        $this->options = $options;
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

        $conn = new Connection();
        $conn->establish($this->options['host'], $this->options['port']);

        $connReq = new ConnectRequest(new Buffer(), 0x02);
        $connReq->send($conn);
        $connRes = new ConnectResponse(0x02);
        $connRes->receive($conn);

        if ($this->auth) {
            $authReq = new AuthRequest(new Buffer(), $this->auth['username'], $this->auth['password']);
            $authReq->send($conn);
            $authRes = new AuthResponse();
            $authRes->receive($conn);
        }

        $detailsReq = new DetailsRequest(new Buffer(), $uriParts['host'], $uriParts['port']);
        $detailsReq->send($conn);
        $detailsRes = new DetailsResponse();
        $detailsRes->receive($conn);

        $stream = $conn->stream();

        if ($uriParts['scheme'] === 'tls') {
            $stream->enableEncryption($options['tls'] ?? []);
        }

        return $stream;
    }
}
