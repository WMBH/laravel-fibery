<?php

namespace WMBH\Fibery\Api;

use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class FieldManager
{
    protected FiberyClient $client;

    // Common field types
    public const TYPE_TEXT = 'fibery/text';

    public const TYPE_NUMBER = 'fibery/number';

    public const TYPE_DATE = 'fibery/date';

    public const TYPE_DATE_TIME = 'fibery/date-time';

    public const TYPE_DATE_RANGE = 'fibery/date-range';

    public const TYPE_CHECKBOX = 'fibery/checkbox';

    public const TYPE_EMAIL = 'fibery/email';

    public const TYPE_URL = 'fibery/url';

    public const TYPE_RICH_TEXT = 'Collaboration~Documents/Document';

    public function __construct(FiberyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new field on a type.
     *
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function create(string $typeName, string $fieldName, string $fieldType, array $options = []): array
    {
        $args = array_merge([
            'fibery/type' => $typeName,
            'fibery/name' => $fieldName,
            'fibery/field-type' => $fieldType,
        ], $options);

        $response = $this->client->command('fibery.field/create', $args);

        return $response['result'] ?? [];
    }

    /**
     * Create a text field.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function createTextField(string $typeName, string $fieldName, array $options = []): array
    {
        return $this->create($typeName, $fieldName, self::TYPE_TEXT, $options);
    }

    /**
     * Create a number field.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function createNumberField(string $typeName, string $fieldName, array $options = []): array
    {
        return $this->create($typeName, $fieldName, self::TYPE_NUMBER, $options);
    }

    /**
     * Create a date field.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function createDateField(string $typeName, string $fieldName, array $options = []): array
    {
        return $this->create($typeName, $fieldName, self::TYPE_DATE, $options);
    }

    /**
     * Create a checkbox field.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function createCheckboxField(string $typeName, string $fieldName, array $options = []): array
    {
        return $this->create($typeName, $fieldName, self::TYPE_CHECKBOX, $options);
    }

    /**
     * Create a single-select field.
     *
     * @param  array<string>  $options  Available options for the select
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function createSingleSelectField(string $typeName, string $fieldName, array $options = []): array
    {
        $args = [
            'fibery/type' => $typeName,
            'fibery/name' => $fieldName,
            'fibery/field-type' => 'enum',
        ];

        if (! empty($options)) {
            $args['fibery/enum-values'] = $options;
        }

        $response = $this->client->command('fibery.field/create', $args);

        return $response['result'] ?? [];
    }

    /**
     * Create a relation field (link to another type).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function createRelationField(string $typeName, string $fieldName, string $targetType, array $options = []): array
    {
        $args = array_merge([
            'fibery/type' => $typeName,
            'fibery/name' => $fieldName,
            'fibery/relation' => [
                'fibery/relation-type' => $targetType,
            ],
        ], $options);

        $response = $this->client->command('fibery.field/create', $args);

        return $response['result'] ?? [];
    }

    /**
     * Rename a field.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function rename(string $typeName, string $currentFieldName, string $newFieldName): array
    {
        $response = $this->client->command('fibery.field/rename', [
            'fibery/type' => $typeName,
            'fibery/name' => $currentFieldName,
            'fibery/new-name' => $newFieldName,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Delete a field.
     *
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function delete(string $typeName, string $fieldName): array
    {
        $response = $this->client->command('fibery.field/delete', [
            'fibery/type' => $typeName,
            'fibery/name' => $fieldName,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Update field metadata.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     *
     * @throws FiberyException
     */
    public function updateMetadata(string $typeName, string $fieldName, array $metadata): array
    {
        $args = array_merge([
            'fibery/type' => $typeName,
            'fibery/name' => $fieldName,
        ], $metadata);

        $response = $this->client->command('fibery.field/update', $args);

        return $response['result'] ?? [];
    }
}
