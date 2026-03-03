<?php

namespace WMBH\Fibery\Api;

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class DocumentManager
{
    protected FiberyClient $client;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
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
        return $this->client->rawRequest('GET', "api/documents/{$documentSecret}");
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
        return $this->client->rawRequest('PUT', "api/documents/{$documentSecret}", [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $content,
        ]);
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
}
