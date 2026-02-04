<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use WMBH\Fibery\Api\WebhookManager;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

beforeEach(function () {
    $this->fiberyClient = new FiberyClient('test-workspace', 'test-token');
});

function createWebhookManagerWithMockHttp(FiberyClient $fiberyClient, MockHandler $mock): WebhookManager
{
    $handlerStack = HandlerStack::create($mock);
    $mockHttpClient = new Client(['handler' => $handlerStack]);

    $manager = new WebhookManager($fiberyClient);

    // Replace the http client using reflection
    $reflection = new ReflectionClass($manager);
    $httpProperty = $reflection->getProperty('http');
    $httpProperty->setAccessible(true);
    $httpProperty->setValue($manager, $mockHttpClient);

    return $manager;
}

it('creates a webhook', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 5,
            'url' => 'https://example.com/webhook',
            'type' => 'Space/Task',
            'state' => 'active',
            'version' => '2',
        ])),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $result = $manager->create('https://example.com/webhook', 'Space/Task');

    expect($result['id'])->toBe(5);
    expect($result['url'])->toBe('https://example.com/webhook');
    expect($result['type'])->toBe('Space/Task');
    expect($result['state'])->toBe('active');
});

it('lists all webhooks', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            [
                'id' => 1,
                'url' => 'https://example.com/webhook1',
                'type' => 'Space/Task',
                'state' => 'active',
            ],
            [
                'id' => 2,
                'url' => 'https://example.com/webhook2',
                'type' => 'Space/Project',
                'state' => 'active',
            ],
        ])),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $result = $manager->all();

    expect($result)->toHaveCount(2);
    expect($result[0]['id'])->toBe(1);
    expect($result[1]['id'])->toBe(2);
});

it('gets a webhook by id', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            [
                'id' => 1,
                'url' => 'https://example.com/webhook1',
                'type' => 'Space/Task',
            ],
            [
                'id' => 5,
                'url' => 'https://example.com/webhook5',
                'type' => 'Space/Project',
            ],
        ])),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $result = $manager->get(5);

    expect($result)->not->toBeNull();
    expect($result['id'])->toBe(5);
    expect($result['url'])->toBe('https://example.com/webhook5');
});

it('returns null for non-existent webhook', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            ['id' => 1, 'url' => 'https://example.com/webhook1', 'type' => 'Space/Task'],
        ])),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $result = $manager->get(999);

    expect($result)->toBeNull();
});

it('deletes a webhook', function () {
    $mock = new MockHandler([
        new Response(204, []),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $result = $manager->delete(5);

    expect($result)->toBeTrue();
});

it('gets webhooks by type', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            ['id' => 1, 'url' => 'https://example.com/webhook1', 'type' => 'Space/Task'],
            ['id' => 2, 'url' => 'https://example.com/webhook2', 'type' => 'Space/Project'],
            ['id' => 3, 'url' => 'https://example.com/webhook3', 'type' => 'Space/Task'],
        ])),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $result = $manager->getByType('Space/Task');

    expect($result)->toHaveCount(2);
    expect($result[0]['id'])->toBe(1);
    expect($result[1]['id'])->toBe(3);
});

it('checks if webhook exists', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            ['id' => 1, 'url' => 'https://example.com/webhook1', 'type' => 'Space/Task'],
        ])),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    expect($manager->exists(1))->toBeTrue();
});

it('checks if webhook does not exist', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            ['id' => 1, 'url' => 'https://example.com/webhook1', 'type' => 'Space/Task'],
        ])),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    expect($manager->exists(999))->toBeFalse();
});

it('throws exception on invalid json response', function () {
    $mock = new MockHandler([
        new Response(200, [], 'not valid json'),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $manager->all();
})->throws(FiberyException::class, 'Invalid JSON response from webhook list');

it('throws exception on create with invalid json', function () {
    $mock = new MockHandler([
        new Response(200, [], 'not valid json'),
    ]);

    $manager = createWebhookManagerWithMockHttp($this->fiberyClient, $mock);

    $manager->create('https://example.com/webhook', 'Space/Task');
})->throws(FiberyException::class, 'Invalid JSON response from webhook creation');
