<?php

namespace WMBH\Fibery\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class DocumentManager
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
     * Get document content by its secret.
     *
     * @return array<string, mixed> Document content in Fibery's format
     *
     * @throws FiberyException
     */
    public function getContent(string $documentSecret): array
    {
        try {
            $response = $this->http->get("api/documents/{$documentSecret}", [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                    'Accept' => 'application/json',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new FiberyException('Invalid JSON response from document API');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new FiberyException('Failed to get document: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Update document content.
     *
     * @param  array<string, mixed>  $content  Document content in Fibery's format
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function updateContent(string $documentSecret, array $content): array
    {
        try {
            $response = $this->http->put("api/documents/{$documentSecret}", [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $content,
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => true];
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new FiberyException('Failed to update document: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Set document content as markdown.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function setMarkdown(string $documentSecret, string $markdown): array
    {
        return $this->updateContent($documentSecret, [
            'content' => [
                'doc' => [
                    'type' => 'doc',
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $markdown,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Append content to a document.
     *
     * @param  array<string, mixed>  $newContent
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function appendContent(string $documentSecret, array $newContent): array
    {
        $existing = $this->getContent($documentSecret);
        $existingContent = $existing['content']['doc']['content'] ?? [];

        $merged = array_merge($existingContent, $newContent);

        return $this->updateContent($documentSecret, [
            'content' => [
                'doc' => [
                    'type' => 'doc',
                    'content' => $merged,
                ],
            ],
        ]);
    }

    /**
     * Get the API token from the client.
     */
    protected function getToken(): string
    {
        $getToken = \Closure::bind(function () {
            return $this->token;
        }, $this->client, FiberyClient::class);

        return $getToken();
    }
}
