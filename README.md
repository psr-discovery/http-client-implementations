**Lightweight library that discovers available [PSR-18 HTTP Clients](https://www.php-fig.org/psr/psr-18/) implementations by searching for a list of well-known classes that implement the relevant interface, and returns an instance of the first one that is found.**

This package is part of the [psr-discovery/discovery](https://github.com/psr-discovery/discovery) PSR discovery collection, which also supports [PSR-17 HTTP Factories](https://github.com/psr-discovery/http-factory-implementations), [PSR-14 Event Dispatchers](https://github.com/psr-discovery/event-dispatcher-implementations), [PSR-11 Containers](https://github.com/psr-discovery/container-implementations), [PSR-6 Cache](https://github.com/psr-discovery/cache-implementations) and [PSR-3 Loggers](https://github.com/psr-discovery/log-implementations).

This is largely intended for inclusion in libraries like SDKs that wish to support PSR-18 HTTP Clients without requiring hard dependencies on specific implementations or demanding extra configuration by users.

-   [Requirements](#requirements)
-   [Implementations](#implementations)
-   [Installation](#installation)
-   [Usage](#usage)
-   [Handling Failures](#handling-failures)
-   [Exceptions](#exceptions)
-   [Singletons](#singletons)
-   [Mocking Priority](#mocking-priority)
-   [Preferring an Implementation](#preferring-an-implementation)
-   [Using a Specific Implementation](#using-a-specific-implementation)

## Requirements

-   PHP 8.0+
-   Composer 2.0+

Successful discovery requires the presence of a compatible implementation in the host application. This library does not install any implementations for you.

## Implementations

The discovery of available implementations is based on a list of well-known libraries that provide the `psr/http-client-implementation` interface. These include:

-   ...

If [a particular implementation](https://packagist.org/providers/psr/http-client-implementation) is missing that you'd like to see, please open a pull request adding support.

## Installation

```bash
composer require --dev psr-discovery/http-client-implementations
```

## Usage

```php
use PsrDiscovery\Discovery;

// Return an instance of the first discovered PSR-18 HTTP Client implementation.
$httpClient = Discovery::httpClient();

// Send a request using the discovered HTTP Client.
$httpClient->sendRequest(...);
```

## Handling Failures

If the library is unable to discover a suitable PSR-18 implementation, the `Discovery::httpClient()` discovery method will simply return `null`. This allows you to handle the failure gracefully, for example by falling back to a default implementation.

Example:

```php
use PsrDiscovery\Discovery;

$httpClient = Discovery::httpClient();

if ($httpClient === null) {
    // No suitable HTTP Client implementation was discovered.
    // Fall back to a default implementation.
    $httpClient = new DefaultHttpClient();
}
```

## Singletons

By default, the `Discovery::httpClient()` method will always return a new instance of the discovered implementation. If you wish to use a singleton instance instead, simply pass `true` to the `$singleton` parameter of the discovery method.

Example:

```php
use PsrDiscovery\Discovery;

// $httpClient1 !== $httpClient2 (default)
$httpClient1 = Discovery::httpClient();
$httpClient2 = Discovery::httpClient();

// $httpClient1 === $httpClient2
$httpClient1 = Discovery::httpClient(singleton: true);
$httpClient2 = Discovery::httpClient(singleton: true);
```

## Mocking Priority

This library will give priority to searching for an available PSR mocking library, like `psr-mock/http-client-implementation` or `php-http/mock-client`.

The expectation is that these mocking libraries will always be installed as development dependencies, and therefore if they are available, they are intended to be used.

## Preferring an Implementation

If you wish to prefer a specific implementation over others, you can `prefer()` it by package name:

```php
use PsrDiscovery\Discovery;
use PsrDiscovery\Implementations\Psr18\Clients;

// Prefer the a specific implementation of PSR-17 over others.
Clients::prefer('guzzlehttp/guzzle');

// Return an instance of GuzzleHttp\Client,
// or the next available from the list of candidates,
// Returns null if none are discovered.
$factory = Discovery::httpClient();
```

This will cause the `httpClient()` method to return the preferred implementation if it is available, otherwise, it will fall back to the default behavior.

Note that assigning a preferred implementation will give it priority over the default preference of mocking libraries.

## Using a Specific Implementation

If you wish to force a specific implementation and ignore the rest of the discovery candidates, you can `use()` its package name:

```php
use PsrDiscovery\Discovery;
use PsrDiscovery\Implementations\Psr18\Clients;

// Only discover a specific implementation of PSR-17.
Clients::use('guzzlehttp/guzzle');

// Return an instance of GuzzleHttp\Client,
// or null if it is not available.
$factory = Discovery::httpClient();
```

This will cause the `httpClient()` method to return the preferred implementation if it is available, otherwise, it will return `null`.

---

This library is not produced or endorsed by, or otherwise affiliated with, the PHP-FIG.
