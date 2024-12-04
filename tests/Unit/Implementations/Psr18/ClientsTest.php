<?php

use PsrDiscovery\Implementations\Psr18\Clients;
use PsrDiscovery\Collections\CandidatesCollection;
use PsrDiscovery\Entities\CandidateEntity;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

beforeEach(function () {
    // Reset state before each test.
    Clients::use(null);

    // Use reflection to set private static properties.
    $reflection = new ReflectionClass(Clients::class);

    // Use a package name that is installed to satisfy Composer's package check.
    $mockPackageName = 'psr/http-client';
    $mockPackageVersion = '^1.0';

    // Set 'candidates' and 'extendedCandidates' to a custom collection with safe candidates.
    $customCandidates = CandidatesCollection::create([
        $mockPackageName => CandidateEntity::create(
            package: $mockPackageName,
            version: $mockPackageVersion,
            builder: static fn () => new class implements ClientInterface {
                public function sendRequest(RequestInterface $request): ResponseInterface
                {
                    // Return a mock response.
                    return new class implements ResponseInterface {
                        // Implement ResponseInterface methods.
                        public function getProtocolVersion() { return '1.1'; }
                        public function withProtocolVersion($version) { return $this; }
                        public function getHeaders() { return []; }
                        public function hasHeader($name) { return false; }
                        public function getHeader($name) { return []; }
                        public function getHeaderLine($name) { return ''; }
                        public function withHeader($name, $value) { return $this; }
                        public function withAddedHeader($name, $value) { return $this; }
                        public function withoutHeader($name) { return $this; }
                        public function getBody() { return new class implements \Psr\Http\Message\StreamInterface {
                            public function __toString() { return ''; }
                            public function close() {}
                            public function detach() {}
                            public function getSize() { return null; }
                            public function tell() { return 0; }
                            public function eof() { return true; }
                            public function isSeekable() { return false; }
                            public function seek($offset, $whence = SEEK_SET) {}
                            public function rewind() {}
                            public function isWritable() { return false; }
                            public function write($string) { return 0; }
                            public function isReadable() { return false; }
                            public function read($length) { return ''; }
                            public function getContents() { return ''; }
                            public function getMetadata($key = null) { return null; }
                        }; }
                        public function withBody(\Psr\Http\Message\StreamInterface $body) { return $this; }
                        public function getStatusCode() { return 200; }
                        public function withStatus($code, $reasonPhrase = '') { return $this; }
                        public function getReasonPhrase() { return 'OK'; }
                    };
                }
            },
        ),
    ]);

    // Set 'candidates' and 'extendedCandidates' to the custom collection.
    foreach (['candidates', 'extendedCandidates'] as $property) {
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue(null, $customCandidates);
    }

    // Reset 'singleton' and 'using' to null.
    foreach (['singleton', 'using'] as $property) {
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }
});

it('discovers a client implementation from custom candidates', function () {
    // Call the discover method.
    $client = Clients::discover();

    // Assert that the client is our mock client.
    expect($client)->toBeInstanceOf(ClientInterface::class);
});

it('retrieves all discovered client implementations', function () {
    $candidates = Clients::discoveries();

    expect($candidates)->toBeArray();
    expect(count($candidates))->toBeGreaterThan(0);

    // Build each candidate to get the client instance.
    foreach ($candidates as $candidate) {
        $client = $candidate->build();
        expect($client)->toBeInstanceOf(ClientInterface::class);
    }
});

it('returns the singleton client or discovered one if none is set', function () {
    // Case 1: No client set, expect singleton() to return the discovered client.
    $client = Clients::singleton();
    expect($client)->toBeInstanceOf(ClientInterface::class);

    // Case 2: Set a mock client and verify it's returned.
    /** @var ClientInterface $mockClient */
    $mockClient = mock(ClientInterface::class);
    Clients::use($mockClient);

    expect(Clients::singleton())->toBe($mockClient);
});

it('adds a candidate and resets the singleton client', function () {
    // Ensure the singleton client is set.
    /** @var ClientInterface $mockClient */
    $mockClient = mock(ClientInterface::class);
    Clients::use($mockClient);
    expect(Clients::singleton())->toBe($mockClient);

    // Add a candidate with an installed package name.
    $candidate = CandidateEntity::create(
        package: 'psr/http-client',
        version: '^1.0',
        builder: static fn () => mock(ClientInterface::class),
    );
    Clients::add($candidate);

    // Verify the candidate was added.
    $candidates = Clients::candidates();
    expect($candidates->has('psr/http-client'))->toBeTrue();
    expect($candidates->get('psr/http-client'))->toBe($candidate);

    // Verify the singleton client has been reset and a new client is discovered.
    $singletonClient = Clients::singleton();
    expect($singletonClient)->toBeInstanceOf(ClientInterface::class);
    expect($singletonClient)->not->toBe($mockClient);
});

it('sets and clears the singleton client using use()', function () {
    /** @var ClientInterface $mockClient */
    $mockClient = mock(ClientInterface::class);

    // Set a specific client.
    Clients::use($mockClient);
    expect(Clients::singleton())->toBe($mockClient);

    // Clear the current client.
    Clients::use(null);
    // After clearing, singleton should return the discovered client.
    $singletonClient = Clients::singleton();
    expect($singletonClient)->toBeInstanceOf(ClientInterface::class);
    expect($singletonClient)->not->toBe($mockClient);
});

it('adds and clears candidates', function () {
    $collection = CandidatesCollection::create();
    $candidate = CandidateEntity::create(
        package: 'psr/http-client',
        version: '^1.0',
        builder: static fn () => mock(ClientInterface::class),
    );

    Clients::set($collection);
    expect(Clients::candidates()->all())->toBeEmpty();

    Clients::add($candidate);
    expect(Clients::candidates()->all())->toHaveKey('psr/http-client');
});

it('prioritizes a preferred candidate', function () {
    $candidate1 = CandidateEntity::create(
        package: 'package/one',
        version: '^1.0',
        builder: static fn () => mock(ClientInterface::class),
    );

    $candidate2 = CandidateEntity::create(
        package: 'package/two',
        version: '^1.0',
        builder: static fn () => mock(ClientInterface::class),
    );

    $collection = CandidatesCollection::create([
        'package/one' => $candidate1,
        'package/two' => $candidate2,
    ]);

    // Use reflection to set 'candidates' and 'extendedCandidates' to our custom collection.
    $reflection = new ReflectionClass(Clients::class);

    foreach (['candidates', 'extendedCandidates'] as $property) {
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue(null, $collection);
    }

    Clients::prefer('package/two');

    $candidates = array_keys(Clients::candidates()->all());
    expect($candidates[0])->toBe('package/two');
});

it('uses a custom CandidatesCollection for allCandidates', function () {
    // Create a custom collection.
    $customCollection = CandidatesCollection::create([
        'psr/http-client' => CandidateEntity::create(
            package: 'psr/http-client',
            version: '^1.0',
            builder: static fn () => mock(ClientInterface::class),
        ),
    ]);

    // Use reflection to set both 'candidates' and 'extendedCandidates' to the custom collection.
    $reflection = new ReflectionClass(Clients::class);

    foreach (['candidates', 'extendedCandidates'] as $property) {
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue(null, $customCollection);
    }

    // Verify allCandidates returns the custom collection.
    $allCandidates = Clients::allCandidates();
    expect($allCandidates->all())->toHaveKey('psr/http-client');
    expect($allCandidates->get('psr/http-client'))->toBeInstanceOf(CandidateEntity::class);
});

it('returns a CandidatesCollection instance from candidates()', function () {
    $candidates = Clients::candidates();
    expect($candidates)->toBeInstanceOf(CandidatesCollection::class);
});

it('returns the same CandidatesCollection instance upon multiple calls', function () {
    $firstCall = Clients::candidates();
    $secondCall = Clients::candidates();
    expect($firstCall)->toBe($secondCall);
});

it('initializes default candidates in candidates()', function () {
    // Reset the candidates to ensure fresh initialization.
    $reflection = new ReflectionClass(Clients::class);
    $prop = $reflection->getProperty('candidates');
    $prop->setAccessible(true);
    $prop->setValue(null, null);

    $candidates = Clients::candidates();
    expect($candidates)->toBeInstanceOf(CandidatesCollection::class);
    expect($candidates->all())->not->toBeEmpty();
});

it('returns a CandidatesCollection instance from allCandidates()', function () {
    $allCandidates = Clients::allCandidates();
    expect($allCandidates)->toBeInstanceOf(CandidatesCollection::class);
});

it('returns the same CandidatesCollection instance upon multiple calls to allCandidates()', function () {
    $firstCall = Clients::allCandidates();
    $secondCall = Clients::allCandidates();
    expect($firstCall)->toBe($secondCall);
});

it('initializes extended candidates in allCandidates()', function () {
    // Reset the extendedCandidates to ensure fresh initialization.
    $reflection = new ReflectionClass(Clients::class);
    $prop = $reflection->getProperty('extendedCandidates');
    $prop->setAccessible(true);
    $prop->setValue(null, null);

    $allCandidates = Clients::allCandidates();
    expect($allCandidates)->toBeInstanceOf(CandidatesCollection::class);
    expect($allCandidates->all())->not->toBeEmpty();
});

it('does not alter candidates when prefer() is called with a non-existent package', function () {
    // Attempt to prefer a package not in the candidates list.
    Clients::prefer('non-existent/package');

    $candidates = Clients::candidates();
    expect($candidates->all())->not->toHaveKey('non-existent/package');
});

it('reorders candidates when prefer() is called with an existing package', function () {
    // Ensure the candidates are initialized.
    Clients::candidates();

    // Prefer an existing package.
    Clients::prefer('psr/http-client');

    $candidates = array_keys(Clients::candidates()->all());
    expect($candidates[0])->toBe('psr/http-client');
});

it('resets singleton and using clients when use() is called with null', function () {
    // Set up an initial singleton client.
    /** @var ClientInterface $initialClient */
    $initialClient = mock(ClientInterface::class);
    Clients::use($initialClient);
    expect(Clients::singleton())->toBe($initialClient);

    // Use null to reset the singleton and using clients.
    Clients::use(null);

    // Verify that the singleton client has been reset.
    $singletonClient = Clients::singleton();
    expect($singletonClient)->toBeInstanceOf(ClientInterface::class);
    expect($singletonClient)->not->toBe($initialClient);
});

it('returns the client set by use() when discover() is called', function () {
    /** @var ClientInterface $mockClient */
    $mockClient = mock(ClientInterface::class);
    Clients::use($mockClient);

    // Call discover and expect to get the client we set with use()
    $client = Clients::discover();
    expect($client)->toBe($mockClient);
});
