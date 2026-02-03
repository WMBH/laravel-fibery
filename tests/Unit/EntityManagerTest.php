<?php

use WMBH\Fibery\Api\EntityManager;
use WMBH\Fibery\FiberyClient;

beforeEach(function () {
    $this->client = Mockery::mock(FiberyClient::class);
    $this->manager = new EntityManager($this->client);
});

it('creates an entity', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/create', [
            'type' => 'Space/Task',
            'entity' => [
                'fibery/name' => 'New Task',
                'Space/Priority' => 5,
            ],
        ])
        ->andReturn(['result' => ['fibery/id' => 'new-uuid']]);

    $result = $this->manager->create('Space/Task', [
        'fibery/name' => 'New Task',
        'Space/Priority' => 5,
    ]);

    expect($result['fibery/id'])->toBe('new-uuid');
});

it('updates an entity', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/update', [
            'type' => 'Space/Task',
            'entity' => [
                'fibery/id' => 'existing-uuid',
                'fibery/name' => 'Updated Task',
            ],
        ])
        ->andReturn(['result' => ['success' => true]]);

    $result = $this->manager->update('Space/Task', 'existing-uuid', [
        'fibery/name' => 'Updated Task',
    ]);

    expect($result['success'])->toBeTrue();
});

it('deletes an entity', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/delete', [
            'type' => 'Space/Task',
            'entity' => ['fibery/id' => 'delete-uuid'],
        ])
        ->andReturn(['result' => ['success' => true]]);

    $result = $this->manager->delete('Space/Task', 'delete-uuid');

    expect($result['success'])->toBeTrue();
});

it('creates or updates an entity', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/create-or-update', [
            'type' => 'Space/Task',
            'entity' => ['fibery/name' => 'Task'],
            'conflict-fields' => ['fibery/name'],
        ])
        ->andReturn(['result' => ['fibery/id' => 'uuid']]);

    $result = $this->manager->createOrUpdate('Space/Task', ['fibery/name' => 'Task'], ['fibery/name']);

    expect($result['fibery/id'])->toBe('uuid');
});

it('adds items to collection', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/add-collection-items', [
            'type' => 'Space/Task',
            'field' => 'Space/Tags',
            'entity' => [
                'entity-uuid' => ['tag-1', 'tag-2'],
            ],
        ])
        ->andReturn(['result' => ['success' => true]]);

    $result = $this->manager->addToCollection('Space/Task', 'entity-uuid', 'Space/Tags', ['tag-1', 'tag-2']);

    expect($result['success'])->toBeTrue();
});

it('removes items from collection', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/remove-collection-items', Mockery::type('array'))
        ->andReturn(['result' => ['success' => true]]);

    $result = $this->manager->removeFromCollection('Space/Task', 'entity-uuid', 'Space/Tags', ['tag-1']);

    expect($result['success'])->toBeTrue();
});

it('sets collection items', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/set-collection-items', Mockery::type('array'))
        ->andReturn(['result' => ['success' => true]]);

    $result = $this->manager->setCollection('Space/Task', 'entity-uuid', 'Space/Tags', ['tag-1', 'tag-2']);

    expect($result['success'])->toBeTrue();
});

it('clears collection', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/reset-collection-items', Mockery::type('array'))
        ->andReturn(['result' => ['success' => true]]);

    $result = $this->manager->clearCollection('Space/Task', 'entity-uuid', 'Space/Tags');

    expect($result['success'])->toBeTrue();
});

it('batch creates multiple entities', function () {
    $this->client->shouldReceive('batch')
        ->once()
        ->with(Mockery::on(function ($commands) {
            return count($commands) === 2
                && $commands[0]['command'] === 'fibery.entity/create'
                && $commands[1]['command'] === 'fibery.entity/create';
        }))
        ->andReturn([
            ['result' => ['fibery/id' => 'uuid-1']],
            ['result' => ['fibery/id' => 'uuid-2']],
        ]);

    $result = $this->manager->createMany('Space/Task', [
        ['fibery/name' => 'Task 1'],
        ['fibery/name' => 'Task 2'],
    ]);

    expect($result)->toHaveCount(2);
});

it('batch updates multiple entities', function () {
    $this->client->shouldReceive('batch')
        ->once()
        ->with(Mockery::on(function ($commands) {
            return count($commands) === 2
                && $commands[0]['command'] === 'fibery.entity/update';
        }))
        ->andReturn([
            ['result' => ['success' => true]],
            ['result' => ['success' => true]],
        ]);

    $result = $this->manager->updateMany('Space/Task', [
        ['fibery/id' => 'uuid-1', 'fibery/name' => 'Updated 1'],
        ['fibery/id' => 'uuid-2', 'fibery/name' => 'Updated 2'],
    ]);

    expect($result)->toHaveCount(2);
});

it('batch deletes multiple entities', function () {
    $this->client->shouldReceive('batch')
        ->once()
        ->with(Mockery::on(function ($commands) {
            return count($commands) === 2
                && $commands[0]['command'] === 'fibery.entity/delete';
        }))
        ->andReturn([
            ['result' => ['success' => true]],
            ['result' => ['success' => true]],
        ]);

    $result = $this->manager->deleteMany('Space/Task', ['uuid-1', 'uuid-2']);

    expect($result)->toHaveCount(2);
});
