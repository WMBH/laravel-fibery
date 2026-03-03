<?php

namespace WMBH\Fibery\Api;

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class WebhookManager
{
    protected FiberyClient $client;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a webhook for a type.
     *
     * @return array<string, mixed> The created webhook data
     *
     * @throws FiberyException
     */
    public function create(string $url, string $type): array
    {
        return $this->client->rawRequest('POST', 'api/webhooks/v2', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'url' => $url,
                'type' => $type,
            ],
        ]);
    }

    /**
     * List all webhooks.
     *
     * @return array<int, array<string, mixed>> Array of webhook data
     *
     * @throws FiberyException
     */
    public function all(): array
    {
        return $this->client->rawRequest('GET', 'api/webhooks/v2');
    }

    /**
     * Get a webhook by ID.
     *
     * @return array<string, mixed>|null The webhook data or null if not found
     *
     * @throws FiberyException
     */
    public function get(int $id): ?array
    {
        $webhooks = $this->all();

        foreach ($webhooks as $webhook) {
            if (isset($webhook['id']) && $webhook['id'] === $id) {
                return $webhook;
            }
        }

        return null;
    }

    /**
     * Delete a webhook by ID.
     *
     * @throws FiberyException
     */
    public function delete(int $id): bool
    {
        $this->client->rawDownload('DELETE', "api/webhooks/v2/{$id}");

        return true;
    }

    /**
     * Get webhooks filtered by type.
     *
     * @return array<int, array<string, mixed>> Array of webhooks for the given type
     *
     * @throws FiberyException
     */
    public function getByType(string $type): array
    {
        $webhooks = $this->all();

        return array_values(array_filter($webhooks, function ($webhook) use ($type) {
            return isset($webhook['type']) && $webhook['type'] === $type;
        }));
    }

    /**
     * Check if a webhook exists.
     *
     * @throws FiberyException
     */
    public function exists(int $id): bool
    {
        return $this->get($id) !== null;
    }
}
