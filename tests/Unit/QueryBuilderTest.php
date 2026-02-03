<?php

use WMBH\Fibery\FiberyClient;
use WMBH\Fibery\Query\QueryBuilder;

beforeEach(function () {
    $this->client = Mockery::mock(FiberyClient::class);
});

it('builds a basic query', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id', 'fibery/name'])
        ->toArray();

    expect($query['query'])->toBe([
        'q/from' => 'Space/Task',
        'q/select' => ['fibery/id', 'fibery/name'],
    ]);
});

it('adds where clause with equals operator', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->where('Space/Status', 'Active')
        ->toArray();

    expect($query['query']['q/where'])->toBe(['=', ['Space/Status'], '$p0']);
    expect($query['params']['$p0'])->toBe('Active');
});

it('adds where clause with custom operator', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->where('Space/Priority', '>', 5)
        ->toArray();

    expect($query['query']['q/where'])->toBe(['>', ['Space/Priority'], '$p0']);
    expect($query['params']['$p0'])->toBe(5);
});

it('combines multiple where clauses with AND', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->where('Space/Status', 'Active')
        ->where('Space/Priority', '>', 3)
        ->toArray();

    expect($query['query']['q/where'][0])->toBe('q/and');
});

it('adds whereIn clause', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->whereIn('Space/Status', ['Active', 'Pending'])
        ->toArray();

    expect($query['query']['q/where'])->toBe(['q/in', ['Space/Status'], '$p0']);
    expect($query['params']['$p0'])->toBe(['Active', 'Pending']);
});

it('adds whereNull clause', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->whereNull('Space/Assignee')
        ->toArray();

    expect($query['query']['q/where'])->toBe(['=', ['Space/Assignee'], null]);
});

it('adds whereNotNull clause', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->whereNotNull('Space/Assignee')
        ->toArray();

    expect($query['query']['q/where'])->toBe(['!=', ['Space/Assignee'], null]);
});

it('adds order by clause', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->orderBy('fibery/creation-date', 'desc')
        ->toArray();

    expect($query['query']['q/order-by'])->toBe([[['fibery/creation-date'], 'q/desc']]);
});

it('adds multiple order by clauses', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->orderBy('Space/Priority', 'desc')
        ->orderBy('fibery/name', 'asc')
        ->toArray();

    expect($query['query']['q/order-by'])->toHaveCount(2);
});

it('adds limit', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->limit(10)
        ->toArray();

    expect($query['query']['q/limit'])->toBe(10);
});

it('adds no limit', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->noLimit()
        ->toArray();

    expect($query['query']['q/limit'])->toBe('q/no-limit');
});

it('adds offset', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->offset(20)
        ->toArray();

    expect($query['query']['q/offset'])->toBe(20);
});

it('uses skip and take aliases', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->skip(10)
        ->take(5)
        ->toArray();

    expect($query['query']['q/offset'])->toBe(10);
    expect($query['query']['q/limit'])->toBe(5);
});

it('includes relations with array', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->with('Space/Assignee', ['fibery/id', 'fibery/name'])
        ->toArray();

    expect($query['query']['q/select'])->toHaveKey('Space/Assignee');
    expect($query['query']['q/select']['Space/Assignee'])->toBe(['fibery/id', 'fibery/name']);
});

it('includes relations with closure', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->with('Space/Tags', function ($q) {
            $q->select(['fibery/id', 'Space/name'])
                ->limit(5);
        })
        ->toArray();

    expect($query['query']['q/select']['Space/Tags'])->toBe([
        'q/select' => ['fibery/id', 'Space/name'],
        'q/limit' => 5,
    ]);
});

it('adds count aggregate', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');

    $query = $builder
        ->select(['fibery/id'])
        ->withCount('task_count', ['Space/Subtasks', 'fibery/id'])
        ->toArray();

    expect($query['query']['q/select']['task_count'])->toBe(['q/count', ['Space/Subtasks', 'fibery/id']]);
});

it('executes get query', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/query', Mockery::type('array'))
        ->andReturn(['result' => [['fibery/id' => 'uuid-1'], ['fibery/id' => 'uuid-2']]]);

    $builder = new QueryBuilder($this->client, 'Space/Task');
    $results = $builder->select(['fibery/id'])->get();

    expect($results)->toHaveCount(2);
    expect($results[0]['fibery/id'])->toBe('uuid-1');
});

it('executes first query', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->with('fibery.entity/query', Mockery::type('array'))
        ->andReturn(['result' => [['fibery/id' => 'uuid-1']]]);

    $builder = new QueryBuilder($this->client, 'Space/Task');
    $result = $builder->select(['fibery/id'])->first();

    expect($result['fibery/id'])->toBe('uuid-1');
});

it('returns null when first finds nothing', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->andReturn(['result' => []]);

    $builder = new QueryBuilder($this->client, 'Space/Task');
    $result = $builder->select(['fibery/id'])->first();

    expect($result)->toBeNull();
});

it('checks exists', function () {
    $this->client->shouldReceive('command')
        ->once()
        ->andReturn(['result' => [['fibery/id' => 'uuid-1']]]);

    $builder = new QueryBuilder($this->client, 'Space/Task');
    $exists = $builder->where('fibery/id', 'uuid-1')->exists();

    expect($exists)->toBeTrue();
});

it('uses default select when none specified', function () {
    $builder = new QueryBuilder($this->client, 'Space/Task');
    $query = $builder->toArray();

    expect($query['query']['q/select'])->toBe(['fibery/id']);
});
