<?php

namespace WMBH\Fibery\Api;

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class SchemaManager
{
    protected FiberyClient $client;

    /** @var array<mixed>|null */
    protected ?array $cachedSchema = null;

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get the full workspace schema.
     *
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function getSchema(bool $fresh = false): array
    {
        if ($fresh || $this->cachedSchema === null) {
            $response = $this->client->command('fibery.schema/query');
            $this->cachedSchema = $response['result'] ?? [];
        }

        return $this->cachedSchema;
    }

    /**
     * Get all types (databases) in the workspace.
     *
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function getTypes(): array
    {
        $schema = $this->getSchema();

        return array_filter($schema['fibery/types'] ?? [], function ($type) {
            // Filter out internal types
            $name = $type['fibery/name'] ?? '';

            return ! str_starts_with($name, 'fibery/')
                && ! str_starts_with($name, 'Collaboration~')
                && ! str_starts_with($name, 'Files/');
        });
    }

    /**
     * Get a specific type by name.
     *
     * @return array<string, mixed>|null
     *
     * @throws FiberyException
     */
    public function getType(string $typeName): ?array
    {
        $schema = $this->getSchema();
        $types = $schema['fibery/types'] ?? [];

        foreach ($types as $type) {
            if (($type['fibery/name'] ?? '') === $typeName) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Get all fields for a type.
     *
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function getFields(string $typeName): array
    {
        $type = $this->getType($typeName);

        return $type['fibery/fields'] ?? [];
    }

    /**
     * Get all spaces in the workspace.
     *
     * @return array<string>
     *
     * @throws FiberyException
     */
    public function getSpaces(): array
    {
        $types = $this->getTypes();
        $spaces = [];

        foreach ($types as $type) {
            $name = $type['fibery/name'] ?? '';
            if (str_contains($name, '/')) {
                $space = explode('/', $name)[0];
                if (! in_array($space, $spaces)) {
                    $spaces[] = $space;
                }
            }
        }

        return $spaces;
    }

    /**
     * Get all types in a specific space.
     *
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function getTypesInSpace(string $space): array
    {
        $types = $this->getTypes();

        return array_filter($types, function ($type) use ($space) {
            $name = $type['fibery/name'] ?? '';

            return str_starts_with($name, $space.'/');
        });
    }

    /**
     * Clear the cached schema.
     */
    public function clearCache(): void
    {
        $this->cachedSchema = null;
    }

    /**
     * Check if a type exists.
     *
     * @throws FiberyException
     */
    public function typeExists(string $typeName): bool
    {
        return $this->getType($typeName) !== null;
    }

    /**
     * Get field metadata for a specific field.
     *
     * @return array<string, mixed>|null
     *
     * @throws FiberyException
     */
    public function getField(string $typeName, string $fieldName): ?array
    {
        $fields = $this->getFields($typeName);

        foreach ($fields as $field) {
            if (($field['fibery/name'] ?? '') === $fieldName) {
                return $field;
            }
        }

        return null;
    }
}
