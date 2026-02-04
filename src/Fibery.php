<?php

namespace WMBH\Fibery;

use WMBH\Fibery\Api\DocumentManager;
use WMBH\Fibery\Api\EntityManager;
use WMBH\Fibery\Api\FieldManager;
use WMBH\Fibery\Api\FileManager;
use WMBH\Fibery\Api\SchemaManager;
use WMBH\Fibery\Api\TypeManager;
use WMBH\Fibery\Api\WebhookManager;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\Query\QueryBuilder;

class Fibery
{
    protected FiberyClient $client;

    protected ?EntityManager $entityManager = null;

    protected ?SchemaManager $schemaManager = null;

    protected ?TypeManager $typeManager = null;

    protected ?FieldManager $fieldManager = null;

    protected ?FileManager $fileManager = null;

    protected ?DocumentManager $documentManager = null;

    protected ?WebhookManager $webhookManager = null;

    public function __construct(string $workspace, string $token, array $options = [])
    {
        $this->client = new FiberyClient(
            $workspace,
            $token,
            $options['timeout'] ?? 30,
            $options['retry_times'] ?? 3,
            $options['retry_sleep'] ?? 1000
        );
    }

    /**
     * Start a query builder for a type.
     */
    public function query(string $type): QueryBuilder
    {
        return new QueryBuilder($this->client, $type);
    }

    /**
     * Alias for query().
     */
    public function from(string $type): QueryBuilder
    {
        return $this->query($type);
    }

    // -------------------------------------------------------------------------
    // Entity Operations (shortcuts)
    // -------------------------------------------------------------------------

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
        return $this->entity()->create($type, $data);
    }

    /**
     * Update an entity.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function update(string $type, string $id, array $data): array
    {
        return $this->entity()->update($type, $id, $data);
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
        return $this->entity()->delete($type, $id);
    }

    /**
     * Find an entity by ID.
     *
     * @param  array<string>  $fields
     * @return array<string, mixed>|null
     *
     * @throws FiberyException
     */
    public function find(string $type, string $id, array $fields = ['fibery/id', 'fibery/name']): ?array
    {
        return $this->query($type)
            ->select($fields)
            ->where('fibery/id', $id)
            ->first();
    }

    /**
     * Find an entity by public ID.
     *
     * @param  array<string>  $fields
     * @return array<string, mixed>|null
     *
     * @throws FiberyException
     */
    public function findByPublicId(string $type, string $publicId, array $fields = ['fibery/id', 'fibery/name']): ?array
    {
        return $this->query($type)
            ->select($fields)
            ->where('fibery/public-id', $publicId)
            ->first();
    }

    // -------------------------------------------------------------------------
    // Collection Operations (shortcuts)
    // -------------------------------------------------------------------------

    /**
     * Add items to a collection field.
     *
     * @param  array<string>  $itemIds
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function addToCollection(string $type, string $entityId, string $field, array $itemIds): array
    {
        return $this->entity()->addToCollection($type, $entityId, $field, $itemIds);
    }

    /**
     * Remove items from a collection field.
     *
     * @param  array<string>  $itemIds
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function removeFromCollection(string $type, string $entityId, string $field, array $itemIds): array
    {
        return $this->entity()->removeFromCollection($type, $entityId, $field, $itemIds);
    }

    /**
     * Set collection items (replaces all).
     *
     * @param  array<string>  $itemIds
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function setCollection(string $type, string $entityId, string $field, array $itemIds): array
    {
        return $this->entity()->setCollection($type, $entityId, $field, $itemIds);
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
        return $this->entity()->clearCollection($type, $entityId, $field);
    }

    // -------------------------------------------------------------------------
    // Raw Command Access
    // -------------------------------------------------------------------------

    /**
     * Execute a raw command.
     *
     * @param  array<string, mixed>  $args
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function command(string $command, array $args = []): array
    {
        return $this->client->command($command, $args);
    }

    /**
     * Execute multiple commands in a batch.
     *
     * @param  array<array{command: string, args: array<string, mixed>}>  $commands
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function batch(array $commands): array
    {
        return $this->client->batch($commands);
    }

    // -------------------------------------------------------------------------
    // Manager Accessors
    // -------------------------------------------------------------------------

    /**
     * Get the entity manager for CRUD operations.
     */
    public function entity(): EntityManager
    {
        if ($this->entityManager === null) {
            $this->entityManager = new EntityManager($this->client);
        }

        return $this->entityManager;
    }

    /**
     * Get the schema manager.
     */
    public function schema(): SchemaManager
    {
        if ($this->schemaManager === null) {
            $this->schemaManager = new SchemaManager($this->client);
        }

        return $this->schemaManager;
    }

    /**
     * Get the type manager for database operations.
     */
    public function types(): TypeManager
    {
        if ($this->typeManager === null) {
            $this->typeManager = new TypeManager($this->client);
        }

        return $this->typeManager;
    }

    /**
     * Get the field manager.
     */
    public function fields(): FieldManager
    {
        if ($this->fieldManager === null) {
            $this->fieldManager = new FieldManager($this->client);
        }

        return $this->fieldManager;
    }

    /**
     * Get the file manager for uploads/downloads.
     */
    public function files(): FileManager
    {
        if ($this->fileManager === null) {
            $this->fileManager = new FileManager($this->client);
        }

        return $this->fileManager;
    }

    /**
     * Get the document manager for rich text fields.
     */
    public function documents(): DocumentManager
    {
        if ($this->documentManager === null) {
            $this->documentManager = new DocumentManager($this->client);
        }

        return $this->documentManager;
    }

    /**
     * Get the webhook manager for webhook operations.
     */
    public function webhooks(): WebhookManager
    {
        if ($this->webhookManager === null) {
            $this->webhookManager = new WebhookManager($this->client);
        }

        return $this->webhookManager;
    }

    /**
     * Get the underlying HTTP client.
     */
    public function getClient(): FiberyClient
    {
        return $this->client;
    }

    /**
     * Get the workspace name.
     */
    public function getWorkspace(): string
    {
        return $this->client->getWorkspace();
    }

    /**
     * Get the base URI for API calls.
     */
    public function getBaseUri(): string
    {
        return $this->client->getBaseUri();
    }

    /**
     * Test the connection to Fibery.
     *
     * @throws FiberyException
     */
    public function testConnection(): bool
    {
        $schema = $this->client->getSchema();

        return isset($schema['result']);
    }
}
