# PhpSocks

[![Build Status](https://github.com/rovazh/phpsocks/actions/workflows/tests.yml/badge.svg)](https://github.com/rovazh/phpsocks/actions?query=workflow%3ABuild)

SOCKS5 proxy client, written in pure PHP with zero dependencies.

### Features
- Supports SOCKS v5
- Supports CONNECT command
- Supports username/password authentication

### Requirements

PHP version 7.4 or higher

### Installation

```bash
composer require rovazh/phpsocks
```

### Usage

#### Plain TCP connections

Connecting to `example.net` on port 80 through a SOCKS5 proxy server.

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1', // SOCKS5 server ipv4 or ipv6 or hostname
    'port' => 1080, // SOCKS5 server port
]);

try {
    $stream = $client->connect('tcp://example.com:80');
    $stream->write("GET / HTTP/1.0\r\n\r\n");
    echo $stream->readAll();
    $stream->close();
} catch (\PhpSocks\Exception\PhpSocksException $e) {
    // Handle exception
}
```

#### Secure TLS connections

The following example demonstrates establishing a secure TLS connection
to `example.net` on port 443 through a SOCKS5 proxy server.

```php
$client = new \PhpSocks\Client(['host' => '127.0.0.1', 'port' => 1080]);

try {
    $stream = $client->connect('tls://example.net:443', [
        'tls' => [
            'peer_name' => 'example.net',
        ]
    ]);
    $stream->write("GET / HTTP/1.0\r\n\r\n");
    echo $stream->readAll();
    $stream->close();
} catch (\PhpSocks\Exception\PhpSocksException $e) {
    // Handle exception
}
```

The `connect` method accepts an associative array of
[SSL context options](http://php.net/manual/en/context.ssl.php)
that can be used to configure TLS when connecting to a destination host.

Please note that SSL context options do not take any effect when using plain TCP connection `tcp://`.

#### Authentication

The library supports username/password authentication
for SOCKS5 servers as defined in RFC 1929.

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

#### Timeout

By default, the library relies on
[default_socket_timeout](https://www.php.net/manual/en/filesystem.configuration.php#ini.default-socket-timeout)
when connecting to a SOCKS5 server.
To set it at runtime, you can use the `connect_timeout` option when creating an instance of the `Client`.

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1',
    'port' => 1080,
    'connect_timeout' => 5.0, // 5 seconds
]);
```

There is also a timeout for sending/receiving data to/from a SOCKS5 server and a destination host.
By default, this timeout is set by the underlying operating system.
To explicitly set it when creating an instance of the `Client`, use the `timeout` option.

```php
$client = new \PhpSocks\Client([
    'host' => '127.0.0.1',
    'port' => 1080,
    'connect_timeout' => 5.0, // 5 seconds
    'timeout' => 3, // 3 seconds
]);
```

### License

The code for PhpSocks is distributed under the terms of the MIT license (see [LICENSE](LICENSE)).
