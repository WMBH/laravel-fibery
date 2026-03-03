<?php

namespace WMBH\Fibery\Query;

use Closure;
use WMBH\Fibery\Exceptions\FiberyException;
use WMBH\Fibery\FiberyClient;

class QueryBuilder
{
    protected FiberyClient $client;

    protected string $type;

    /** @var array<int|string, string|array<string, mixed>> */
    protected array $select = [];

    /** @var array<mixed>|null */
    protected ?array $where = null;

    /** @var array<array<mixed>> */
    protected array $orderBy = [];

    protected ?int $limit = null;

    protected bool $noLimit = false;

    protected int $offset = 0;

    /** @var array<string, mixed> */
    protected array $params = [];

    protected int $paramCounter = 0;

    public function __construct(FiberyClient $client, string $type)
    {
        $this->client = $client;
        $this->type = $type;
    }

    /**
     * Set the fields to select.
     *
     * @param  array<int, string|array<string, mixed>>  $fields
     */
    public function select(array $fields): self
    {
        $this->select = $fields;

        return $this;
    }

    /**
     * Add a field to the select list.
     */
    public function addSelect(string|array $field): self
    {
        $this->select[] = $field;

        return $this;
    }

    /**
     * Add a where clause to the query.
     *
     * @param  string|array<mixed>  $field  Field name or raw where array
     * @param  mixed  $value
     */
    public function where(string|array $field, ?string $operator = null, $value = null): self
    {
        // If field is an array, it's a raw where clause
        if (is_array($field)) {
            $this->addWhereClause($field);

            return $this;
        }

        // If only two arguments, assume '=' operator
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $paramName = $this->createParam($value);
        $clause = [$operator, [$field], $paramName];

        $this->addWhereClause($clause);

        return $this;
    }

    /**
     * Add a where in clause.
     *
     * @param  array<mixed>  $values
     */
    public function whereIn(string $field, array $values): self
    {
        $paramName = $this->createParam($values);
        $clause = ['q/in', [$field], $paramName];

        $this->addWhereClause($clause);

        return $this;
    }

    /**
     * Add a where null clause.
     */
    public function whereNull(string $field): self
    {
        $clause = ['=', [$field], null];
        $this->addWhereClause($clause);

        return $this;
    }

    /**
     * Add a where not null clause.
     */
    public function whereNotNull(string $field): self
    {
        $clause = ['!=', [$field], null];
        $this->addWhereClause($clause);

        return $this;
    }

    /**
     * Add an OR where clause.
     *
     * @param  array<array<mixed>>  $conditions
     */
    public function orWhere(array $conditions): self
    {
        $orClauses = ['q/or'];
        foreach ($conditions as $condition) {
            if (count($condition) === 3) {
                [$field, $operator, $value] = $condition;
                $paramName = $this->createParam($value);
                $orClauses[] = [$operator, [$field], $paramName];
            }
        }

        $this->addWhereClause($orClauses);

        return $this;
    }

    /**
     * Include a relation in the query.
     *
     * @param  array<string>|Closure  $fieldsOrCallback
     */
    public function with(string $relation, array|Closure $fieldsOrCallback): self
    {
        if ($fieldsOrCallback instanceof Closure) {
            $subQuery = new SubQueryBuilder;
            $fieldsOrCallback($subQuery);
            $this->select[$relation] = $subQuery->toArray();
        } else {
            $this->select[$relation] = $fieldsOrCallback;
        }

        return $this;
    }

    /**
     * Add an aggregate function to the select.
     *
     * @param  array<string>  $field  Field path as array
     */
    public function withAggregate(string $alias, string $function, array $field): self
    {
        $this->select[$alias] = [$function, $field];

        return $this;
    }

    /**
     * Add a count aggregate.
     *
     * @param  array<string>  $field
     */
    public function withCount(string $alias, array $field): self
    {
        return $this->withAggregate($alias, 'q/count', $field);
    }

    /**
     * Order the results.
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $dir = strtolower($direction) === 'desc' ? 'q/desc' : 'q/asc';
        $this->orderBy[] = [[$field], $dir];

        return $this;
    }

    /**
     * Order by descending.
     */
    public function orderByDesc(string $field): self
    {
        return $this->orderBy($field, 'desc');
    }

    /**
     * Set the maximum number of results.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        $this->noLimit = false;

        return $this;
    }

    /**
     * Remove any limit, returning all results.
     */
    public function noLimit(): self
    {
        $this->noLimit = true;
        $this->limit = null;

        return $this;
    }

    /**
     * Set the offset for pagination.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Alias for offset.
     */
    public function skip(int $offset): self
    {
        return $this->offset($offset);
    }

    /**
     * Alias for limit.
     */
    public function take(int $limit): self
    {
        return $this->limit($limit);
    }

    /**
     * Execute the query and return all results.
     *
     * @return array<mixed>
     *
     * @throws FiberyException
     */
    public function get(): array
    {
        $response = $this->client->command('fibery.entity/query', [
            'query' => $this->buildQuery(),
            'params' => $this->params ?: new \stdClass,
        ]);

        return $response['result'] ?? [];
    }

    /**
     * Execute the query and return the first result.
     *
     * @return array<string, mixed>|null
     *
     * @throws FiberyException
     */
    public function first(): ?array
    {
        $originalLimit = $this->limit;
        $this->limit = 1;

        $results = $this->get();

        $this->limit = $originalLimit;

        return $results[0] ?? null;
    }

    /**
     * Execute a count query.
     *
     * @throws FiberyException
     */
    public function count(): int
    {
        // For count, we select just the ID and count the results
        $originalSelect = $this->select;
        $originalLimit = $this->limit;
        $originalNoLimit = $this->noLimit;

        $this->select = ['fibery/id'];
        $this->noLimit();

        $results = $this->get();

        $this->select = $originalSelect;
        $this->limit = $originalLimit;
        $this->noLimit = $originalNoLimit;

        return count($results);
    }

    /**
     * Check if any records exist matching the query.
     *
     * @throws FiberyException
     */
    public function exists(): bool
    {
        return $this->first() !== null;
    }

    /**
     * Get the raw query array without executing.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query' => $this->buildQuery(),
            'params' => $this->params ?: new \stdClass,
        ];
    }

    /**
     * Build the query array for the Fibery API.
     *
     * @return array<string, mixed>
     */
    protected function buildQuery(): array
    {
        $query = [
            'q/from' => $this->type,
            'q/select' => $this->buildSelect(),
        ];

        if ($this->where !== null) {
            $query['q/where'] = $this->where;
        }

        if (! empty($this->orderBy)) {
            $query['q/order-by'] = $this->orderBy;
        }

        if ($this->noLimit) {
            $query['q/limit'] = 'q/no-limit';
        } elseif ($this->limit !== null) {
            $query['q/limit'] = $this->limit;
        }

        if ($this->offset > 0) {
            $query['q/offset'] = $this->offset;
        }

        return $query;
    }

    /**
     * Build the select array for the query.
     *
     * @return array<mixed>
     */
    protected function buildSelect(): array
    {
        if (empty($this->select)) {
            return ['fibery/id'];
        }

        $result = [];
        foreach ($this->select as $key => $value) {
            if (is_string($key)) {
                // Named key means it's a relation or aggregate
                $result[$key] = $value;
            } else {
                // Numeric key means it's a simple field
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Add a where clause, combining with AND if there are existing clauses.
     *
     * @param  array<mixed>  $clause
     */
    protected function addWhereClause(array $clause): void
    {
        if ($this->where === null) {
            $this->where = $clause;
        } elseif (($this->where[0] ?? null) === 'q/and') {
            // If existing where is already an 'and', append to it
            $this->where[] = $clause;
        } else {
            // Wrap existing and new in 'and'
            $this->where = ['q/and', $this->where, $clause];
        }
    }

    /**
     * Create a parameter and return its reference.
     */
    protected function createParam(mixed $value): string
    {
        $paramName = '$p'.$this->paramCounter++;
        $this->params[$paramName] = $value;

        return $paramName;
    }
}
