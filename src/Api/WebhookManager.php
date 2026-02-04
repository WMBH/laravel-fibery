<?php

namespace WMBH\Fibery\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class WebhookManager
{
    protected FiberyClient $client;

    protected Client $http;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
        $this->http = new Client([
            'base_uri' => $client->getBaseUri(),
            'timeout' => 30,
        ]);
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
        try {
            $response = $this->http->post('api/webhooks/v2', [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'url' => $url,
                    'type' => $type,
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new FiberyException('Invalid JSON response from webhook creation');
            }

            return $data;
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $responseBody = $e->getResponse()->getBody()->getContents();

            throw new FiberyException("Webhook creation failed (HTTP {$statusCode}): {$responseBody}", $statusCode, $e);
        } catch (GuzzleException $e) {
            throw new FiberyException('Webhook creation failed: '.$e->getMessage(), 0, $e);
        }
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
        try {
            $response = $this->http->get('api/webhooks/v2', [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new FiberyException('Invalid JSON response from webhook list');
            }

            return $data;
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $responseBody = $e->getResponse()->getBody()->getContents();

            throw new FiberyException("Webhook list failed (HTTP {$statusCode}): {$responseBody}", $statusCode, $e);
        } catch (GuzzleException $e) {
            throw new FiberyException('Webhook list failed: '.$e->getMessage(), 0, $e);
        }
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
        try {
            $this->http->delete("api/webhooks/v2/{$id}", [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                ],
            ]);

            return true;
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $responseBody = $e->getResponse()->getBody()->getContents();

            throw new FiberyException("Webhook deletion failed (HTTP {$statusCode}): {$responseBody}", $statusCode, $e);
        } catch (GuzzleException $e) {
            throw new FiberyException('Webhook deletion failed: '.$e->getMessage(), 0, $e);
        }
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

    /**
     * Get the API token from the client.
     */
    protected function getToken(): string
    {
        // Access protected property via closure binding
        $getToken = \Closure::bind(function () {
            return $this->token;
        }, $this->client, FiberyClient::class);

        return $getToken();
    }
}
