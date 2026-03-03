<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use WMBH\Fibery\Exceptions\AuthenticationException;
use WMBH\Fibery\Exceptions\ConnectionException;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\Exceptions\RateLimitException;
use WMBH\Fibery\Exceptions\TimeoutException;
use WMBH\Fibery\FiberyClient;

function createClientWithMockHttp(MockHandler $mock, int $retryTimes = 3, int $retrySleep = 0): FiberyClient
{
    $client = new FiberyClient('test-workspace', 'test-token', 30, $retryTimes, $retrySleep);

    $handlerStack = HandlerStack::create($mock);
    $mockHttpClient = new Client(['handler' => $handlerStack]);

    $reflection = new ReflectionClass($client);
    $httpProperty = $reflection->getProperty('http');
    $httpProperty->setAccessible(true);
    $httpProperty->setValue($client, $mockHttpClient);

    return $client;
}

// --- getToken() ---

it('exposes token via getToken', function () {
    $client = new FiberyClient('test-workspace', 'my-secret-token');
    expect($client->getToken())->toBe('my-secret-token');
});

// --- rawRequest() ---

it('makes a raw GET request and returns decoded JSON', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['data' => 'value'])),
    ]);

    $client = createClientWithMockHttp($mock);
    $result = $client->rawRequest('GET', 'api/webhooks/v2');

    expect($result)->toBe(['data' => 'value']);
});

it('makes a raw POST request with options', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['id' => 1])),
    ]);

    $client = createClientWithMockHttp($mock);
    $result = $client->rawRequest('POST', 'api/webhooks/v2', [
        'json' => ['url' => 'https://example.com', 'type' => 'Space/Task'],
    ]);

    expect($result)->toBe(['id' => 1]);
});

it('throws FiberyException on invalid JSON from rawRequest', function () {
    $mock = new MockHandler([
        new Response(200, [], 'not json'),
    ]);

    $client = createClientWithMockHttp($mock);
    $client->rawRequest('GET', 'api/test');
})->throws(FiberyException::class);

it('throws AuthenticationException on 401 from rawRequest', function () {
    $mock = new MockHandler([
        new Response(401, [], json_encode(['error' => 'unauthorized'])),
    ]);

    $client = createClientWithMockHttp($mock);
    $client->rawRequest('GET', 'api/test');
})->throws(AuthenticationException::class);

it('retries on 429 and throws RateLimitException when exhausted from rawRequest', function () {
    $mock = new MockHandler([
        new Response(429, ['Retry-After' => '2'], ''),
        new Response(429, ['Retry-After' => '2'], ''),
        new Response(429, ['Retry-After' => '2'], ''),
    ]);

    $client = createClientWithMockHttp($mock, retryTimes: 3, retrySleep: 0);
    $client->rawRequest('GET', 'api/test');
})->throws(RateLimitException::class);

it('parses Retry-After header into RateLimitException', function () {
    $mock = new MockHandler([
        new Response(429, ['Retry-After' => '5'], ''),
    ]);

    $client = createClientWithMockHttp($mock, retryTimes: 1, retrySleep: 0);

    try {
        $client->rawRequest('GET', 'api/test');
    } catch (RateLimitException $e) {
        expect($e->getRetryAfter())->toBe(5);

        return;
    }

    test()->fail('Expected RateLimitException was not thrown');
});

it('throws TimeoutException on request timeout', function () {
    $mock = new MockHandler([
        new ConnectException(
            'Connection timed out',
            new Request('GET', 'api/test'),
            null,
            ['errno' => 28, 'error' => 'Operation timed out']
        ),
    ]);

    $client = createClientWithMockHttp($mock);
    $client->rawRequest('GET', 'api/test');
})->throws(TimeoutException::class);

it('throws ConnectionException on connection failure', function () {
    $mock = new MockHandler([
        new ConnectException(
            'Could not resolve host',
            new Request('GET', 'api/test')
        ),
    ]);

    $client = createClientWithMockHttp($mock);
    $client->rawRequest('GET', 'api/test');
})->throws(ConnectionException::class);

// --- rawDownload() ---

it('downloads raw content', function () {
    $mock = new MockHandler([
        new Response(200, [], 'file-content-bytes'),
    ]);

    $client = createClientWithMockHttp($mock);
    $result = $client->rawDownload('GET', 'api/files/secret123');

    expect($result)->toBe('file-content-bytes');
});

// --- command() error handling improvements ---

it('throws TimeoutException on command timeout', function () {
    $mock = new MockHandler([
        new ConnectException(
            'Connection timed out',
            new Request('POST', 'api/commands'),
            null,
            ['errno' => 28, 'error' => 'Operation timed out']
        ),
    ]);

    $client = createClientWithMockHttp($mock);
    $client->command('fibery.schema/query');
})->throws(TimeoutException::class);

it('throws ConnectionException on command connection failure', function () {
    $mock = new MockHandler([
        new ConnectException(
            'Could not resolve host',
            new Request('POST', 'api/commands')
        ),
    ]);

    $client = createClientWithMockHttp($mock);
    $client->command('fibery.schema/query');
})->throws(ConnectionException::class);

it('includes json error detail in exception message', function () {
    $mock = new MockHandler([
        new Response(200, [], 'not-valid-json'),
    ]);

    $client = createClientWithMockHttp($mock);

    try {
        $client->command('fibery.schema/query');
    } catch (FiberyException $e) {
        expect($e->getMessage())->toContain('Invalid JSON');

        return;
    }

    test()->fail('Expected FiberyException was not thrown');
});

it('retries on 429 with Retry-After header for commands', function () {
    $mock = new MockHandler([
        new Response(429, ['Retry-After' => '3'], ''),
        new Response(200, [], json_encode([['result' => ['data' => 'ok']]])),
    ]);

    $client = createClientWithMockHttp($mock, retryTimes: 3, retrySleep: 0);
    $result = $client->command('fibery.schema/query');

    expect($result['result'])->toBe(['data' => 'ok']);
});
