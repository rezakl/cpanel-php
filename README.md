# cPanel/WHM API for PHP

A lightweight, dependency-free PHP client for the cPanel/WHM JSON API, built on cURL.

- Call any WHM API function as a method: `$cpanel->listaccts()`
- API token, access hash and password authentication
- SSL certificate verification on by default
- Clear exceptions for network, HTTP and JSON errors

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Calling the API](#calling-the-api)
- [Error handling](#error-handling)
- [Testing](#testing)
- [Credits](#credits)

## Requirements

- PHP 5.5 or later
- `curl` and `json` extensions

## Installation

```shell
composer require rezakl/cpanel-php
```

## Quick start

```php
<?php
use Rezakl\CpanelPhp\Cpanel;

$cpanel = new Cpanel([
    'host'      => 'https://whm.example.com:2087',
    'username'  => 'root',
    'auth_type' => 'token',
    'password'  => 'YOUR_API_TOKEN',
]);

$accounts = $cpanel->listaccts();
```

## Configuration

### Constructor options

| Option       | Required | Default  | Description                                                       |
|--------------|----------|----------|-------------------------------------------------------------------|
| `host`       | Yes      |          | Full URL with protocol and port, e.g. `https://whm.example.com:2087` |
| `username`   | Yes      |          | WHM username, usually `root`                                      |
| `password`   | Yes      |          | API token, access hash or password, depending on `auth_type`      |
| `auth_type`  | No       | `'hash'` | `'token'`, `'hash'` or `'password'`                               |
| `verify_ssl` | No       | `true`   | Verify the server's SSL certificate                               |
| `timeout`    | No       | `30`     | Request timeout in seconds                                        |

### Authentication types

| `auth_type`  | Header sent                         | Notes                                              |
|--------------|-------------------------------------|----------------------------------------------------|
| `'token'`    | `Authorization: whm user:token`     | **Recommended.** Create tokens in WHM under *Development › Manage API Tokens*. |
| `'hash'`     | `Authorization: WHM user:hash`      | Legacy access hash; removed in recent cPanel versions. |
| `'password'` | `Authorization: Basic base64(user:pass)` | Plain account password.                      |

> **Note:** Only set `verify_ssl` to `false` for test servers with self-signed certificates.
> Disabling it in production exposes your credentials to interception.

### Changing configuration later

Every setter returns the instance, so calls can be chained.

```php
$cpanel
    ->setHost('https://whm2.example.com:2087')
    ->setAuthorization('root', 'NEW_API_TOKEN')
    ->setAuthType('token')
    ->setVerifySsl(true)
    ->setTimeout(60)
    ->setHeader('X-Custom-Header', 'value');
```

### Reading configuration

```php
$cpanel->getHost();
$cpanel->getUsername();
$cpanel->getPassword();
$cpanel->getAuthType();
$cpanel->getVerifySsl();
```

## Calling the API

Any method you call on the client is sent to `{host}/json-api/{method}`. Pass API parameters as an
associative array:

```php
// No parameters
$accounts = $cpanel->listaccts();

// With parameters
$result = $cpanel->createacct([
    'username' => 'foo',
    'domain'   => 'foo.com',
    'plan'     => 'default',
]);
```

Responses are returned as decoded JSON objects (`stdClass`):

```php
foreach ($cpanel->listaccts()->acct as $account) {
    echo $account->user, ' - ', $account->domain, PHP_EOL;
}
```

See the [WHM API documentation](https://api.docs.cpanel.net/whm/introduction) for available functions and their parameters.

## Error handling

| Exception                   | Thrown when                                                    |
|-----------------------------|----------------------------------------------------------------|
| `\RuntimeException`         | The request fails (network or SSL error), the server returns a non-2xx status (the exception code is the HTTP status), or the response is not valid JSON |
| `\InvalidArgumentException` | `auth_type` is unsupported, the function name is invalid, or parameters are not an array |
| `\Exception`                | A required constructor option (`host`, `username`, `password`) is missing |

```php
try {
    $accounts = $cpanel->listaccts();
} catch (\RuntimeException $e) {
    error_log('WHM API error: ' . $e->getMessage());
}
```

## Testing

```shell
composer install
vendor/bin/phpunit
```

The tests mock the HTTP layer and never contact a real server.

## Credits

- Original library: [gufy/cpanel-php](https://github.com/mgufrone/cpanel-php) by Mochamad Gufron
- cURL rewrite: Fafan

## License

MIT
