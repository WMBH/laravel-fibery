<?php

namespace WMBH\Fibery\Query;

class SubQueryBuilder
{
    /** @var array<int, string|array<string, mixed>> */
    protected array $select = [];

    protected ?int $limit = null;

    protected bool $noLimit = false;

    /** @var array<array<mixed>> */
    protected array $orderBy = [];

    /**
     * Set the fields to select in the subquery.
     *
     * @param  array<int, string|array<string, mixed>>  $fields
     */
    public function select(array $fields): self
    {
        $this->select = $fields;

        return $this;
    }

    /**
     * Limit the number of results in the subquery.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        $this->noLimit = false;

        return $this;
    }

    /**
     * Remove limit from the subquery.
     */
    public function noLimit(): self
    {
        $this->noLimit = true;
        $this->limit = null;

        return $this;
    }

    /**
     * Order the subquery results.
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $dir = strtolower($direction) === 'desc' ? 'q/desc' : 'q/asc';
        $this->orderBy[] = [[$field], $dir];

        return $this;
    }

    /**
     * Convert the subquery to an array for the API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'q/select' => $this->select,
        ];

        if ($this->noLimit) {
            $result['q/limit'] = 'q/no-limit';
        } elseif ($this->limit !== null) {
            $result['q/limit'] = $this->limit;
        }

        if (! empty($this->orderBy)) {
            $result['q/order-by'] = $this->orderBy;
        }

        return $result;
    }
}
