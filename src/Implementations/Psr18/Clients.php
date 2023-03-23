<?php

declare(strict_types=1);

namespace PsrDiscovery\Implementations\Psr18;

use Psr\Http\Client\ClientInterface;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Contracts\Implementations\Psr18\ClientsContract;
use PsrDiscovery\Discover;
use PsrDiscovery\Entities\CandidateEntity;
use PsrDiscovery\Implementations\Implementation;

final class Clients extends Implementation implements ClientsContract
{
    private static ?CandidatesCollection $candidates                     = null;
    private static ?ClientInterface $singleton                           = null;
    private static ?ClientInterface $using                               = null;

    public static function add(CandidateEntity $candidate): void
    {
        parent::add($candidate);
        self::use(null);
    }

    /**
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement
     */
    public static function candidates(): CandidatesCollection
    {
        if (null !== self::$candidates) {
            return self::$candidates;
        }

        self::$candidates = CandidatesCollection::create();

        // psr-mock/http-client-implementation 1.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'psr-mock/http-client-implementation',
            version: '^1.0',
            builder: static fn (string $class = '\PsrMock\Psr18\Client'): object => new $class(),
        ));

        // guzzlehttp/guzzle 7.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'guzzlehttp/guzzle',
            version: '^7.0',
            builder: static fn (string $class = '\GuzzleHttp\Client'): object => new $class(),
        ));

        // symfony/http-client 4.3+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'symfony/http-client',
            version: '^4.3',
            builder: static fn (string $class = '\Symfony\Component\HttpClient\Psr18Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
                streamFactory: Discover::httpStreamFactory(),
            ),
        ));

        // php-http/guzzle6-adapter 2.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/guzzle6-adapter',
            version: '^2.0',
            builder: static fn (string $class = '\Http\Adapter\Guzzle6\Client'): object => new $class(),
        ));

        // php-http/guzzle7-adapter 1.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/guzzle7-adapter',
            version: '^1.0',
            builder: static fn (string $class = '\Http\Adapter\Guzzle7\Client'): object => new $class(),
        ));

        // php-http/curl-client 2.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/curl-client',
            version: '^2.0',
            builder: static fn (string $class = '\Http\Client\Curl\Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
                streamFactory: Discover::httpStreamFactory(),
            ),
        ));

        // kriswallsmith/buzz 1.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'kriswallsmith/buzz',
            version: '^1.0',
            builder: static fn (string $class = '\Buzz\Client\FileGetContents'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
            ),
        ));

        // php-http/socket-client 2.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/socket-client',
            version: '^2.0',
            builder: static fn (string $class = '\Http\Client\Socket\Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
            ),
        ));

        // php-http/guzzle5-adapter 2.0+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'php-http/guzzle5-adapter',
            version: '^2.0',
            builder: static fn (string $class = '\Http\Adapter\Guzzle5\Client'): object => new $class(
                responseFactory: Discover::httpResponseFactory(),
            ),
        ));

        // voku/httpful 0.2.20+ is PSR-18 compatible.
        self::$candidates->add(CandidateEntity::create(
            package: 'voku/httpful',
            version: '^0.2.20',
            builder: static fn (string $class = '\Httpful\Client'): object => new $class(),
        ));

        return self::$candidates;
    }

    /**
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement
     */
    public static function discover(): ?ClientInterface
    {
        if (null !== self::$using) {
            return self::$using;
        }

        return Discover::httpClient();
    }

    public static function prefer(string $package): void
    {
        self::$candidates ??= CandidatesCollection::create();
        parent::prefer($package);
        self::use(null);
    }

    public static function set(CandidatesCollection $candidates): void
    {
        self::$candidates ??= CandidatesCollection::create();
        parent::set($candidates);
        self::use(null);
    }

    public static function singleton(): ?ClientInterface
    {
        if (null !== self::$using) {
            return self::$using;
        }

        return self::$singleton ??= self::discover();
    }

    public static function use(?ClientInterface $instance): void
    {
        self::$singleton = $instance;
        self::$using     = $instance;
    }
}
