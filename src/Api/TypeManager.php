<?php

namespace WMBH\Fibery\Api;

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class TypeManager
{
    protected FiberyClient $client;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new database (type) in a space.
     *
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function create(string $space, string $name, array $options = []): array
    {
        $typeName = $space.'/'.$name;

        $args = array_merge([
            'fibery/name' => $typeName,
        ], $options);

        $response = $this->client->command('fibery.type/create', $args);

        return $response['result'] ?? [];
    }

    /**
     * Rename a database (type).
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function rename(string $currentName, string $newName): array
    {
        $response = $this->client->command('fibery.type/rename', [
            'fibery/name' => $currentName,
            'fibery/new-name' => $newName,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Delete a database (type).
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function delete(string $typeName): array
    {
        $response = $this->client->command('fibery.type/delete', [
            'fibery/name' => $typeName,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Update type metadata (like icon, color, etc.).
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function updateMetadata(string $typeName, array $metadata): array
    {
        $args = array_merge([
            'fibery/name' => $typeName,
        ], $metadata);

        $response = $this->client->command('fibery.type/update', $args);

        return $response['result'] ?? [];
    }
}
