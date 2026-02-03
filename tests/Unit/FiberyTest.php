<?php

use WMBH\Fibery\Api\DocumentManager;
use WMBH\Fibery\Api\EntityManager;
use WMBH\Fibery\Api\FieldManager;
use WMBH\Fibery\Api\FileManager;
use WMBH\Fibery\Api\SchemaManager;
use WMBH\Fibery\Api\TypeManager;
use WMBH\Fibery\Fibery;
use WMBH\Fibery\Query\QueryBuilder;

it('creates a fibery instance', function () {
    $fibery = new Fibery('test-workspace', 'test-token');

    expect($fibery->getWorkspace())->toBe('test-workspace');
    expect($fibery->getBaseUri())->toBe('https://test-workspace.fibery.io/');
});

it('creates a fibery instance with options', function () {
    $fibery = new Fibery('test-workspace', 'test-token', [
        'timeout' => 60,
        'retry_times' => 5,
        'retry_sleep' => 2000,
    ]);

    expect($fibery)->toBeInstanceOf(Fibery::class);
});

it('returns a query builder', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $query = $fibery->query('Space/Task');

    expect($query)->toBeInstanceOf(QueryBuilder::class);
});

it('returns a query builder via from alias', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $query = $fibery->from('Space/Task');

    expect($query)->toBeInstanceOf(QueryBuilder::class);
});

it('returns entity manager', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $manager = $fibery->entity();

    expect($manager)->toBeInstanceOf(EntityManager::class);
});

it('returns schema manager', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $manager = $fibery->schema();

    expect($manager)->toBeInstanceOf(SchemaManager::class);
});

it('returns type manager', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $manager = $fibery->types();

    expect($manager)->toBeInstanceOf(TypeManager::class);
});

it('returns field manager', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $manager = $fibery->fields();

    expect($manager)->toBeInstanceOf(FieldManager::class);
});

it('returns file manager', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $manager = $fibery->files();

    expect($manager)->toBeInstanceOf(FileManager::class);
});

it('returns document manager', function () {
    $fibery = new Fibery('test-workspace', 'test-token');
    $manager = $fibery->documents();

    expect($manager)->toBeInstanceOf(DocumentManager::class);
});

it('reuses manager instances', function () {
    $fibery = new Fibery('test-workspace', 'test-token');

    $entity1 = $fibery->entity();
    $entity2 = $fibery->entity();

    expect($entity1)->toBe($entity2);
});
