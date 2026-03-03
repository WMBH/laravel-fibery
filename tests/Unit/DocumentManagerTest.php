<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use WMBH\Fibery\Api\DocumentManager;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

function createDocumentManagerWithMockClient(MockHandler $mock): DocumentManager
{
    $client = new FiberyClient('test-workspace', 'test-token');

    $handlerStack = HandlerStack::create($mock);
    $mockHttpClient = new Client(['handler' => $handlerStack]);

    $reflection = new ReflectionClass($client);
    $httpProperty = $reflection->getProperty('http');
    $httpProperty->setAccessible(true);
    $httpProperty->setValue($client, $mockHttpClient);

    return new DocumentManager($client);
}

it('gets document content', function () {
    $content = ['content' => ['doc' => ['type' => 'doc', 'content' => []]]];
    $mock = new MockHandler([
        new Response(200, [], json_encode($content)),
    ]);

    $manager = createDocumentManagerWithMockClient($mock);
    $result = $manager->getContent('doc-secret-123');

    expect($result)->toBe($content);
});

it('updates document content', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $manager = createDocumentManagerWithMockClient($mock);
    $result = $manager->updateContent('doc-secret-123', ['content' => ['doc' => ['type' => 'doc', 'content' => []]]]);

    expect($result)->toBe(['success' => true]);
});

it('throws exception on invalid JSON in updateContent instead of returning success', function () {
    $mock = new MockHandler([
        new Response(200, [], 'not valid json'),
    ]);

    $manager = createDocumentManagerWithMockClient($mock);
    $manager->updateContent('doc-secret-123', ['content' => []]);
})->throws(FiberyException::class);

it('throws exception on invalid JSON in getContent', function () {
    $mock = new MockHandler([
        new Response(200, [], 'not valid json'),
    ]);

    $manager = createDocumentManagerWithMockClient($mock);
    $manager->getContent('doc-secret-123');
})->throws(FiberyException::class);

it('sets markdown content', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $manager = createDocumentManagerWithMockClient($mock);
    $result = $manager->setMarkdown('doc-secret-123', 'Hello world');

    expect($result)->toBe(['success' => true]);
});

it('appends content to document', function () {
    $existing = ['content' => ['doc' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]]]];
    $mock = new MockHandler([
        new Response(200, [], json_encode($existing)),
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $manager = createDocumentManagerWithMockClient($mock);
    $result = $manager->appendContent('doc-secret-123', [['type' => 'paragraph', 'content' => []]]);

    expect($result)->toBe(['success' => true]);
});
