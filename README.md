

[![Build Status](https://github.com/rovazh/phpsocks/actions/workflows/tests.yml/badge.svg)](https://github.com/rovazh/phpsocks/actions?query=workflow%3ABuild)

SOCKS5 proxy client, written in pure PHP with zero dependencies.

## Features
- Supports SOCKS5 protocol
- Implements the CONNECT command
- Implements UDP ASSOCIATE command
- Supports username/password authentication (RFC 1929)

## Requirements

- PHP 7.4 or higher
- Sockets extension enabled

## Installation

Install via Composer:
```bash
composer require rovazh/phpsocks
```

## Usage

### Tunneling TCP connections through a SOCKS5 server (CONNECT)

#### Plain TCP connections

The following example demonstrates connecting to `example.net` on port `80` via a SOCKS5 proxy server:

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1', // SOCKS5 server (IPv4, IPv6, or hostname)
    'port' => 1080, // SOCKS5 server port
]);

try {
    $stream = $client->connect('tcp://example.com:80');
    $stream->write("GET / HTTP/1.0\r\n\r\n");
    echo $stream->read(1024);
    $stream->close();
} catch (\PhpSocks\Exception\PhpSocksException $e) {
    // Handle exception
}
```

#### Secure TLS connections

The following example demonstrates establishing a secure
TLS connection to `example.net` on port `443` via a SOCKS5 proxy server:

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1',
    'port' => 1080,
]);

try {
    $stream = $client->connect('tls://example.net:443', [
        'tls' => [
            'peer_name' => 'example.net',
        ]
    ]);
    $stream->write("GET / HTTP/1.0\r\n\r\n");
    echo $stream->read(1024);
    $stream->close();
} catch (\PhpSocks\Exception\PhpSocksException $e) {
    // Handle exception
}
```

The connect method accepts an associative array of SSL context options that can be used to
configure TLS settings when connecting to a destination host.

> Note: SSL context options have no effect when using a plain TCP connection (tcp://).

#### Authentication

PhpSocks supports username/password authentication for SOCKS5 servers as defined in RFC 1929.

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1',
    'port' => 1080,
    'auth' => [
        'username' => 'proxy_user',
        'password' => 'proxy_pass',
    ]
]);
```

#### Timeout Settings

By default, the library relies on the system's
[default_socket_timeout](https://www.php.net/manual/en/filesystem.configuration.php#ini.default-socket-timeout)
when connecting to a SOCKS5 server.
To set a custom timeout at runtime, use the `connect_timeout` option:

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1',
    'port' => 1080,
    'connect_timeout' => 5.0, // 5 seconds
]);
```

Additionally, you can set a timeout for sending and receiving data. By default,
this is determined by the operating system. To explicitly define it, use the `timeout` option:

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1',
    'port' => 1080,
    'connect_timeout' => 5.0, // 5 seconds
    'timeout' => 3, // 3 seconds
]);
```

### Relaying UDP Datagrams through a SOCKS5 Server (UDP ASSOCIATE)

The following example establishes a SOCKS5 UDP association to enable relaying UDP
datagrams to `example.net` on port `5023` via a SOCKS5 proxy server:

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1', // SOCKS5 server (IPv4, IPv6, or hostname)
    'port' => 1080, // SOCKS5 server port
]);

try {
    $stream = $client->associate('udp://example.com:5023');
    $stream->write("Hello");
    echo $stream->read(1024);
    $stream->close();
} catch (\PhpSocks\Exception\PhpSocksException $e) {
    // Handle exception
}
```

## License

PhpSocks is distributed under the terms of the MIT License. See [LICENSE](LICENSE) file for details.
