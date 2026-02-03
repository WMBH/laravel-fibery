<?php

namespace WMBH\Fibery\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class FileManager
{
    protected FiberyClient $client;

    protected Client $http;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
        $this->http = new Client([
            'base_uri' => $client->getBaseUri(),
            'timeout' => 60, // Files may take longer
        ]);
    }

    /**
     * Upload a file.
     *
     * @return array<string, mixed> Contains file ID and other metadata
     *
     * @throws FiberyException
     */
    public function upload(string $filePath, ?string $fileName = null): array
    {
        if (! file_exists($filePath)) {
            throw new FiberyException("File not found: {$filePath}");
        }

        $fileName = $fileName ?? basename($filePath);

        try {
            $response = $this->http->post('api/files', [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => $fileName,
                    ],
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new FiberyException('Invalid JSON response from file upload');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new FiberyException('File upload failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Upload a file from content string.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function uploadContent(string $content, string $fileName): array
    {
        try {
            $response = $this->http->post('api/files', [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $content,
                        'filename' => $fileName,
                    ],
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new FiberyException('Invalid JSON response from file upload');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new FiberyException('File upload failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Download a file by its secret.
     *
     * @throws FiberyException
     */
    public function download(string $fileSecret): string
    {
        try {
            $response = $this->http->get("api/files/{$fileSecret}", [
                'headers' => [
                    'Authorization' => 'Token '.$this->getToken(),
                ],
            ]);

            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new FiberyException('File download failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Download a file and save it to a path.
     *
     * @throws FiberyException
     */
    public function downloadTo(string $fileSecret, string $destinationPath): bool
    {
        $content = $this->download($fileSecret);

        return file_put_contents($destinationPath, $content) !== false;
    }

    /**
     * Attach a file to an entity.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function attachToEntity(string $type, string $entityId, string $fileField, string $fileId): array
    {
        $response = $this->client->command('fibery.entity/add-collection-items', [
            'type' => $type,
            'field' => $fileField,
            'entity' => [
                $entityId => [$fileId],
            ],
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Remove a file from an entity.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function removeFromEntity(string $type, string $entityId, string $fileField, string $fileId): array
    {
        $response = $this->client->command('fibery.entity/remove-collection-items', [
            'type' => $type,
            'field' => $fileField,
            'entity' => [
                $entityId => [$fileId],
            ],
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Get the API token from the client (via reflection or stored reference).
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
