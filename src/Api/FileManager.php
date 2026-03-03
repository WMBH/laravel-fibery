<?php

namespace WMBH\Fibery\Api;

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class FileManager
{
    protected FiberyClient $client;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
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

        return $this->client->rawRequest('POST', 'api/files', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => $fileName,
                ],
            ],
        ]);
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
        return $this->client->rawRequest('POST', 'api/files', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $content,
                    'filename' => $fileName,
                ],
            ],
        ]);
    }

    /**
     * Download a file by its secret.
     *
     * @throws FiberyException
     */
    public function download(string $fileSecret): string
    {
        return $this->client->rawDownload('GET', "api/files/{$fileSecret}");
    }

    /**
     * Download a file and save it to a path.
     *
     * @throws FiberyException
     */
    public function downloadTo(string $fileSecret, string $destinationPath): void
    {
        $content = $this->download($fileSecret);

        if (@file_put_contents($destinationPath, $content) === false) {
            throw new FiberyException("Failed to write file to: {$destinationPath}");
        }
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
}
