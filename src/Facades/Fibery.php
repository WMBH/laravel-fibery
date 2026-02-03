<?php

namespace WMBH\Fibery\Facades;

use Illuminate\Support\Facades\Facade;
use WMBH\Fibery\Api\DocumentManager;
use WMBH\Fibery\Api\EntityManager;
use WMBH\Fibery\Api\FieldManager;
use WMBH\Fibery\Api\FileManager;
use WMBH\Fibery\Api\SchemaManager;
use WMBH\Fibery\Api\TypeManager;
use WMBH\Fibery\FiberyClient;
use WMBH\Fibery\Query\QueryBuilder;

/**
 * @method static QueryBuilder query(string $type)
 * @method static QueryBuilder from(string $type)
 * @method static array create(string $type, array $data)
 * @method static array update(string $type, string $id, array $data)
 * @method static array delete(string $type, string $id)
 * @method static array|null find(string $type, string $id, array $fields = ['fibery/id', 'fibery/name'])
 * @method static array|null findByPublicId(string $type, string $publicId, array $fields = ['fibery/id', 'fibery/name'])
 * @method static array addToCollection(string $type, string $entityId, string $field, array $itemIds)
 * @method static array removeFromCollection(string $type, string $entityId, string $field, array $itemIds)
 * @method static array setCollection(string $type, string $entityId, string $field, array $itemIds)
 * @method static array clearCollection(string $type, string $entityId, string $field)
 * @method static array command(string $command, array $args = [])
 * @method static array batch(array $commands)
 * @method static EntityManager entity()
 * @method static SchemaManager schema()
 * @method static TypeManager types()
 * @method static FieldManager fields()
 * @method static FileManager files()
 * @method static DocumentManager documents()
 * @method static FiberyClient getClient()
 * @method static string getWorkspace()
 * @method static string getBaseUri()
 * @method static bool testConnection()
 *
 * @see \WMBH\Fibery\Fibery
 */
class Fibery extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WMBH\Fibery\Fibery::class;
    }
}
