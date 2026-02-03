<?php

namespace WMBH\Fibery\Api;

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class EntityManager
{
    protected FiberyClient $client;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new entity.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function create(string $type, array $data): array
    {
        $response = $this->client->command('fibery.entity/create', [
            'type' => $type,
            'entity' => $data,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Update an existing entity.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function update(string $type, string $id, array $data): array
    {
        $entity = array_merge(['fibery/id' => $id], $data);

        $response = $this->client->command('fibery.entity/update', [
            'type' => $type,
            'entity' => $entity,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Delete an entity.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function delete(string $type, string $id): array
    {
        $response = $this->client->command('fibery.entity/delete', [
            'type' => $type,
            'entity' => [
                'fibery/id' => $id,
            ],
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Create or update an entity based on a unique field.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $uniqueFields  Fields to check for existing entity
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function createOrUpdate(string $type, array $data, array $uniqueFields = []): array
    {
        $args = [
            'type' => $type,
            'entity' => $data,
        ];

        if (! empty($uniqueFields)) {
            $args['conflict-fields'] = $uniqueFields;
        }

        $response = $this->client->command('fibery.entity/create-or-update', $args);

        return $response['result'] ?? [];
    }

    /**
     * Add items to a collection field.
     *
     * @param  array<string>  $itemIds  IDs of items to add
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function addToCollection(string $type, string $entityId, string $field, array $itemIds): array
    {
        $response = $this->client->command('fibery.entity/add-collection-items', [
            'type' => $type,
            'field' => $field,
            'entity' => [
                $entityId => $itemIds,
            ],
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Remove items from a collection field.
     *
     * @param  array<string>  $itemIds  IDs of items to remove
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function removeFromCollection(string $type, string $entityId, string $field, array $itemIds): array
    {
        $response = $this->client->command('fibery.entity/remove-collection-items', [
            'type' => $type,
            'field' => $field,
            'entity' => [
                $entityId => $itemIds,
            ],
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Set collection items (replaces all existing items).
     *
     * @param  array<string>  $itemIds  IDs of items to set
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function setCollection(string $type, string $entityId, string $field, array $itemIds): array
    {
        $response = $this->client->command('fibery.entity/set-collection-items', [
            'type' => $type,
            'field' => $field,
            'entity' => [
                $entityId => $itemIds,
            ],
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Clear all items from a collection field.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function clearCollection(string $type, string $entityId, string $field): array
    {
        $response = $this->client->command('fibery.entity/reset-collection-items', [
            'type' => $type,
            'field' => $field,
            'entity' => [
                $entityId => [],
            ],
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Batch create multiple entities.
     *
     * @param  array<array<string, mixed>>  $entities
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function createMany(string $type, array $entities): array
    {
        $commands = [];
        foreach ($entities as $entity) {
            $commands[] = [
                'command' => 'fibery.entity/create',
                'args' => [
                    'type' => $type,
                    'entity' => $entity,
                ],
            ];
        }

        return $this->client->batch($commands);
    }

    /**
     * Batch update multiple entities.
     *
     * @param  array<array<string, mixed>>  $entities  Each must include 'fibery/id'
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function updateMany(string $type, array $entities): array
    {
        $commands = [];
        foreach ($entities as $entity) {
            $commands[] = [
                'command' => 'fibery.entity/update',
                'args' => [
                    'type' => $type,
                    'entity' => $entity,
                ],
            ];
        }

        return $this->client->batch($commands);
    }

    /**
     * Batch delete multiple entities.
     *
     * @param  array<string>  $ids
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function deleteMany(string $type, array $ids): array
    {
        $commands = [];
        foreach ($ids as $id) {
            $commands[] = [
                'command' => 'fibery.entity/delete',
                'args' => [
                    'type' => $type,
                    'entity' => ['fibery/id' => $id],
                ],
            ];
        }

        return $this->client->batch($commands);
    }
}
