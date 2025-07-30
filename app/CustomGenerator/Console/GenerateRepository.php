<?php

namespace App\CustomGenerator\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateRepository extends GeneratorCommand
{
    use HasModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:repository';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class and interface for a Model';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/../stubs/repository.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Repositories';
    }

    protected function buildClass($name): array|string
    {
        // Generate base files only on first run
        $this->generateBaseFilesIfNeeded();

        $replace = [
            '{{CLASS}}' => $this->getRepositoryName(),
        ];

        $replace = $this->buildModel($replace);
        $replace = $this->buildInterface($replace);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Generate BaseRepository and RepositoryInterface only on first run
     */
    protected function generateBaseFilesIfNeeded(): void
    {
        $baseRepositoryPath = app_path('Repositories/BaseRepository.php');
        $repositoryInterfacePath = app_path('Repositories/Contracts/RepositoryInterface.php');

        // Check if base files don't exist (first run)
        if (! file_exists($baseRepositoryPath) || ! file_exists($repositoryInterfacePath)) {
            // Create Repositories directory structure
            $repositoriesDir = app_path('Repositories');
            $contractsDir = app_path('Repositories/Contracts');

            if (! is_dir($repositoriesDir)) {
                mkdir($repositoriesDir, 0755, true);
            }

            if (! is_dir($contractsDir)) {
                mkdir($contractsDir, 0755, true);
            }

            // Generate BaseRepository if it doesn't exist
            if (! file_exists($baseRepositoryPath)) {
                $this->generateBaseRepository($baseRepositoryPath);
            }

            // Generate RepositoryInterface if it doesn't exist
            if (! file_exists($repositoryInterfacePath)) {
                $this->generateRepositoryInterface($repositoryInterfacePath);
            }
        }
    }

    /**
     * Generate the BaseRepository file
     */
    protected function generateBaseRepository(string $path): void
    {
        $baseRepositoryContent = $this->getBaseRepositoryStub();
        file_put_contents($path, $baseRepositoryContent);

        $this->components->info(sprintf('%s [%s] created successfully.', 'BaseRepository', $path));
    }

    /**
     * Generate the RepositoryInterface file
     */
    protected function generateRepositoryInterface(string $path): void
    {
        $repositoryInterfaceContent = $this->getRepositoryInterfaceStub();
        file_put_contents($path, $repositoryInterfaceContent);

        $this->components->info(sprintf('%s [%s] created successfully.', 'RepositoryInterface', $path));
    }

    /**
     * Get the BaseRepository stub content
     */
    protected function getBaseRepositoryStub(): string
    {
        return '<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\Conditionable;

abstract class BaseRepository implements RepositoryInterface
{
    use Conditionable;

    private Builder $queryBuilder;

    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->queryBuilder = $this->model
            ->newQuery()
            // Added the default base select to have the ability to add more selects using builder addSelect()
            ->select($model->getTable().\'.*\');
    }

    protected function filter(callable $filter): static
    {
        $cloned = clone $this;
        $filter($cloned->queryBuilder);

        return $cloned;
    }

    protected function query(): Builder
    {
        return clone $this->queryBuilder;
    }

    protected function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }

    public function find(mixed $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function save(Model $model): bool
    {
        return $model->save();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function limit(int $limit): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->limit($limit));
    }

    public function pluck($column, $key = null): \Illuminate\Support\Collection
    {
        return $this->queryBuilder->pluck($column, $key);
    }

    public function orderBy($column, $direction = \'asc\'): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->orderBy($column, $direction));
    }

    public function orderByRaw($statement, $bindings = []): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->orderByRaw($statement, $bindings));
    }

    public function get(): Collection
    {
        return $this->queryBuilder->get();
    }

    public function cursor(): LazyCollection
    {
        return $this->queryBuilder->cursor();
    }

    public function paginate($perPage = 10): LengthAwarePaginator
    {
        return $this->queryBuilder->paginate($perPage);
    }

    public function simplePaginate($perPage = 10): Paginator
    {
        return $this->queryBuilder->simplePaginate($perPage);
    }

    public function with(array $relationships): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->with($relationships));
    }

    public function withCount(mixed $relations): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->withCount($relations));
    }

    public function chunkById(int $count, callable $callback): bool
    {
        return $this->query()->chunkById($count, $callback);
    }

    public function exists(): bool
    {
        return $this->queryBuilder->exists();
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    public function sum(Expression|string $column): mixed
    {
        return $this->queryBuilder->sum($column);
    }

    public function toRawSql(): string
    {
        return $this->query()->toRawSql();
    }

    public function first(): ?Model
    {
        return $this->query()->first();
    }

    public function batchDelete(): int
    {
        return $this->query()->delete();
    }

    public function batchUpdate(array $attributes): int
    {
        return $this->query()->update($attributes);
    }

    public function random(): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->inRandomOrder());
    }

    public function distinct(): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->distinct());
    }

    public function select(array $columns): static
    {
        return $this->filter(static fn (Builder $builder) => $builder->select($columns));
    }
}
';
    }

    /**
     * Get the RepositoryInterface stub content
     */
    protected function getRepositoryInterfaceStub(): string
    {
        return '<?php

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

    public function orderBy($column, $direction = \'asc\'): static;

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
';
    }

    protected function buildInterface(array $replace): array
    {
        $interface = $this->getInterfaceName();
        $interfaceClass = $this->qualifyInterface($interface);

        if (interface_exists($interfaceClass)) {
            if ($this->components->confirm("A {$interfaceClass} interface already exists. Do you want to regenerate it?")) {
                $this->call('make:repository-interface', [
                    'name' => $interface,
                    'model' => $this->getModelInput(),
                    '--force' => true,
                ]);
            }
        } else {
            $this->call('make:repository-interface', [
                'name' => $interface,
                'model' => $this->getModelInput(),
            ]);
        }

        return array_merge($replace, [
            '{{ namespacedInterface }}' => $interfaceClass,
            '{{INTERFACE}}' => class_basename($interfaceClass),
        ]);
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the repository already exists'],
        ];
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model name associated with the repository'],
            ['name', InputArgument::OPTIONAL, 'The name of the repository'],
            ['interface', InputArgument::OPTIONAL, 'The name of the repository interface'],
        ];
    }

    protected function getNameInput(): string
    {
        $name = parent::getNameInput();

        if (trim($name) === '') {
            $name = $this->getDefaultRepositoryName();
        }

        if (Str::endsWith($name, 'Repository')) {
            return $name;
        }

        return $name.'Repository';
    }

    protected function getRepositoryName(): string
    {
        return $this->getNameInput();
    }

    protected function getDefaultRepositoryName(): string
    {
        return $this->getModelInput().'Repository';
    }

    protected function getInterfaceName(): string
    {
        $interfaceName = $this->argument('interface');

        if (empty($interfaceName)) {
            return $this->getDefaultInterfaceName();
        }

        if (Str::endsWith($interfaceName, 'RepositoryInterface')) {
            return $interfaceName;
        }

        return $interfaceName.'RepositoryInterface';
    }

    protected function getDefaultInterfaceName(): string
    {
        return $this->getDefaultRepositoryName().'Interface';
    }

    protected function qualifyInterface($name): string
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyInterface(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\'.'Contracts\\'.$name
        );
    }
}
