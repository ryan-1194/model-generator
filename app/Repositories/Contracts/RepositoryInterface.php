<?php

namespace App\Repositories\Contracts;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\Conditionable;

interface RepositoryInterface
{
    public function find(mixed $id): ?Model;

    public function save(Model $model): bool;

    public function delete(Model $model): bool;

    public function pluck($column, $key = null): \Illuminate\Support\Collection;

    public function orderBy($column, $direction = 'asc'): static;

    public function orderByRaw($statement, $bindings = []): static;

    public function get(): Collection;

    public function cursor(): LazyCollection;

    public function paginate($perPage = 10): LengthAwarePaginator;

    public function simplePaginate($perPage = 10): Paginator;

    public function with(array $relationships): static;

    public function withCount(mixed $relations): static;

    public function limit(int $limit): static;

    public function chunkById(int $count, callable $callback): bool;

    public function exists(): bool;

    public function count(): int;

    public function sum(Expression|string $column): mixed;

    public function toRawSql(): string;

    public function first(): ?Model;

    public function batchDelete(): int;

    public function batchUpdate(array $attributes): int;

    public function random(): static;

    public function distinct(): static;

    public function select(array $columns): static;

    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TWhenParameter
     * @template TWhenReturnType
     *
     * @param  (Closure($this): TWhenParameter)|TWhenParameter|null  $value
     * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $callback
     * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $default
     * @return $this|TWhenReturnType
     *
     * @see Conditionable
     */
    public function when($value = null, ?callable $callback = null, ?callable $default = null);

    /**
     * Apply the callback if the given "value" is (or resolves to) falsy.
     *
     * @template TUnlessParameter
     * @template TUnlessReturnType
     *
     * @param  (Closure($this): TUnlessParameter)|TUnlessParameter|null  $value
     * @param  (callable($this, TUnlessParameter): TUnlessReturnType)|null  $callback
     * @param  (callable($this, TUnlessParameter): TUnlessReturnType)|null  $default
     * @return $this|TUnlessReturnType
     *
     * @see Conditionable
     */
    public function unless($value = null, ?callable $callback = null, ?callable $default = null);
}
