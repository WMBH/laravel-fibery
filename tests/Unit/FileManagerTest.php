<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use WMBH\Fibery\Api\FileManager;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

function createFileManagerWithMockClient(MockHandler $mock): FileManager
{
    $client = new FiberyClient('test-workspace', 'test-token');

    $handlerStack = HandlerStack::create($mock);
    $mockHttpClient = new Client(['handler' => $handlerStack]);

    $reflection = new ReflectionClass($client);
    $httpProperty = $reflection->getProperty('http');
    $httpProperty->setAccessible(true);
    $httpProperty->setValue($client, $mockHttpClient);

    return new FileManager($client);
}

it('uploads a file from path', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['id' => 'file-123', 'name' => 'test.txt'])),
    ]);

    $manager = createFileManagerWithMockClient($mock);

    $tmpFile = tempnam(sys_get_temp_dir(), 'fibery_test_');
    file_put_contents($tmpFile, 'test content');

    try {
        $result = $manager->upload($tmpFile, 'test.txt');
        expect($result['id'])->toBe('file-123');
    } finally {
        unlink($tmpFile);
    }
});

it('throws exception when uploading non-existent file', function () {
    $mock = new MockHandler([]);
    $manager = createFileManagerWithMockClient($mock);

    $manager->upload('/non/existent/file.txt');
})->throws(FiberyException::class, 'File not found');

it('uploads content string as file', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['id' => 'file-456'])),
    ]);

    $manager = createFileManagerWithMockClient($mock);
    $result = $manager->uploadContent('file content here', 'content.txt');

    expect($result['id'])->toBe('file-456');
});

it('downloads a file', function () {
    $mock = new MockHandler([
        new Response(200, [], 'raw-file-bytes'),
    ]);

    $manager = createFileManagerWithMockClient($mock);
    $result = $manager->download('secret-abc');

    expect($result)->toBe('raw-file-bytes');
});

it('downloads a file to disk', function () {
    $mock = new MockHandler([
        new Response(200, [], 'file-content-here'),
    ]);

    $manager = createFileManagerWithMockClient($mock);
    $tmpFile = tempnam(sys_get_temp_dir(), 'fibery_dl_');

    try {
        $manager->downloadTo('secret-abc', $tmpFile);
        expect(file_get_contents($tmpFile))->toBe('file-content-here');
    } finally {
        unlink($tmpFile);
    }
});

it('throws exception when downloadTo cannot write to disk', function () {
    $mock = new MockHandler([
        new Response(200, [], 'file-content'),
    ]);

    $manager = createFileManagerWithMockClient($mock);

    $manager->downloadTo('secret-abc', '/non/existent/directory/file.txt');
})->throws(FiberyException::class, 'Failed to write file');

it('throws on invalid JSON from upload', function () {
    $mock = new MockHandler([
        new Response(200, [], 'not json'),
    ]);

    $manager = createFileManagerWithMockClient($mock);

    $tmpFile = tempnam(sys_get_temp_dir(), 'fibery_test_');
    file_put_contents($tmpFile, 'test');

    try {
        $manager->upload($tmpFile);
    } finally {
        unlink($tmpFile);
    }
})->throws(FiberyException::class);
