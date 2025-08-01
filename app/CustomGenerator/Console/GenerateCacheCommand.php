<?php

namespace App\CustomGenerator\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;

class GenerateCacheCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new cache class following the {Model}By{PrimaryKey} pattern';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Cache';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/../stubs/cache.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $modelName = $this->getModelInput();

        return $rootNamespace.'\Cache\\'.$modelName;
    }

    public function handle(): ?bool
    {
        // Generate base files only on the first run
        $this->generateBaseFilesIfNeeded();

        // Validate the model exists before doing anything else
        $modelName = $this->getModelInput();
        $this->validateModel($modelName);

        // Validate the repository exists before doing anything else
        $this->validateRepository($modelName);

        // Call the parent handle method to continue with normal flow
        return parent::handle();
    }

    /**
     * Generate CacheBase and Traits folder only on the first run
     */
    protected function generateBaseFilesIfNeeded(): void
    {
        $cacheBasePath = app_path('Cache/CacheBase.php');
        $withHelpersPath = app_path('Cache/Traits/WithHelpers.php');

        // Check if base files don't exist (first run)
        if (! file_exists($cacheBasePath) || ! file_exists($withHelpersPath)) {
            // Create Cache directory structure
            $cacheDir = app_path('Cache');
            $traitsDir = app_path('Cache/Traits');

            if (! is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            if (! is_dir($traitsDir)) {
                mkdir($traitsDir, 0755, true);
            }

            // Generate CacheBase if it doesn't exist
            if (! file_exists($cacheBasePath)) {
                $this->generateCacheBase($cacheBasePath);
            }

            // Generate WithHelpers trait if it doesn't exist
            if (! file_exists($withHelpersPath)) {
                $this->generateWithHelpers($withHelpersPath);
            }
        }
    }

    /**
     * Generate the CacheBase file
     */
    protected function generateCacheBase(string $path): void
    {
        $cacheBaseContent = $this->getCacheBaseStub();
        file_put_contents($path, $cacheBaseContent);

        $this->components->info(sprintf('%s [%s] created successfully.', 'CacheBase', $path));
    }

    /**
     * Generate the WithHelpers trait file
     */
    protected function generateWithHelpers(string $path): void
    {
        $withHelpersContent = $this->getWithHelpersStub();
        file_put_contents($path, $withHelpersContent);

        $this->components->info(sprintf('%s [%s] created successfully.', 'WithHelpers trait ', $path));
    }

    /**
     * Get the CacheBase stub content
     */
    protected function getCacheBaseStub(): string
    {
        return '<?php

namespace App\Cache;

use App\Cache\Traits\WithHelpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

abstract class CacheBase
{
    use WithHelpers;

    protected $key;

    protected $ttl;

    protected $data;

    abstract protected function cacheMiss();

    abstract protected function errorModelName(): string;

    abstract protected function errorModelId(): mixed;

    public function __construct($key, $ttl)
    {
        $this->key = $key;
        $this->ttl = $ttl;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function invalidate(): bool
    {
        return Cache::forget($this->key);
    }

    public function fetch()
    {
        $this->data = Cache::remember($this->getKey(), $this->ttl, function () {
            return $this->cacheMiss();
        });

        // only store "non-null" data from cacheMiss()
        if (is_null($this->data)) {
            $this->invalidate();
        }

        return $this->data;
    }

    public function fetchOrFail()
    {
        $result = $this->fetch();
        if ($result == null) {
            throw new ModelNotFoundException(\'Unable to retrieve \'.$this->errorModelName().($this->errorModelId() ? (\' \'.$this->errorModelId()) : \'\'));
        } else {
            return $result;
        }
    }

    public function store($data): bool
    {
        return Cache::put($this->getKey(), $data, $this->ttl);
    }

    public function exists(): bool
    {
        return Cache::has($this->getKey());
    }
}
';
    }

    /**
     * Get the WithHelpers trait stub content
     */
    protected function getWithHelpersStub(): string
    {
        return '<?php

namespace App\Cache\Traits;

trait WithHelpers
{
    public static function get(...$params)
    {
        return (new self(...$params))->fetch();
    }

    public static function forget(...$params)
    {
        return (new self(...$params))->invalidate();
    }

    public static function getOrFail(...$params)
    {
        return (new self(...$params))->fetchOrFail();
    }

    public static function forgetTags(...$params)
    {
        return (new self(...$params))->invalidateTags();
    }
}
';
    }

    protected function buildClass($name): array|string
    {
        $modelName = $this->getModelInput();
        $primaryKey = $this->getPrimaryKeyInput();

        $replace = [
            '{{MODEL_NAME}}' => $modelName,
            '{{PRIMARY_KEY}}' => $primaryKey,
            '{{PRIMARY_KEY_LOWER}}' => Str::lower($primaryKey),
            '{{PRIMARY_KEY_TYPE}}' => $this->getPrimaryKeyType(),
            '{{CACHE_KEY}}' => $this->getCacheKey(),
            '{{REPOSITORY_INTERFACE}}' => $modelName.'RepositoryInterface',
        ];

        $replace = $this->buildModelNamespace($replace);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function buildModelNamespace(array $replace): array
    {
        $modelName = $this->getModelInput();
        $modelClass = $this->qualifyModel($modelName);

        return array_merge($replace, [
            '{{ namespacedModel }}' => $modelClass,
            '{{MODEL}}' => class_basename($modelClass),
        ]);
    }

    protected function qualifyModel($model): string
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);
        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return $rootNamespace.'Models\\'.$model;
    }

    protected function getNameInput(): string
    {
        $name = parent::getNameInput();

        if (trim($name) === '') {
            $name = $this->getDefaultCacheName();
        }

        return $name;
    }

    protected function getDefaultCacheName(): string
    {
        $modelName = $this->getModelInput();
        $primaryKey = $this->getPrimaryKeyInput();

        return $modelName.'By'.$primaryKey;
    }

    protected function getModelInput(): string
    {
        return trim($this->argument('model'));
    }

    protected function getPrimaryKeyInput(): string
    {
        $primaryKey = $this->argument('primary_key');

        return $primaryKey ?: 'Id';
    }

    protected function getPrimaryKeyType(): string
    {
        $type = $this->option('type');

        return $type ?: 'int';
    }

    protected function getCacheKey(): string
    {
        $modelName = $this->getModelInput();
        $primaryKey = $this->getPrimaryKeyInput();

        return Str::plural(Str::lower($modelName)).'.{'.'$this->'.Str::lower($primaryKey).'}';
    }

    protected function validateModel(string $modelName): void
    {
        $modelClass = $this->qualifyModel($modelName);

        if (! class_exists($modelClass)) {
            if (confirm("A {$modelClass} model does not exist. Do you want to generate it?")) {
                $this->call('make:model', ['name' => $modelName]);
            } else {
                throw new InvalidArgumentException("Model {$modelClass} does not exist.");
            }
        }
    }

    protected function validateRepository(string $modelName): void
    {
        $repositoryInterface = $this->qualifyRepositoryInterface($modelName);

        // Check if repository interface exists both by interface_exists and by file existence
        // This handles cases where the interface was just created but interface_exists might not reflect it yet
        $interfaceExists = interface_exists($repositoryInterface) || $this->repositoryInterfaceFileExists($repositoryInterface);

        if (! $interfaceExists) {
            if (confirm("A {$repositoryInterface} repository does not exist. Do you want to generate it?")) {
                $this->call('make:repository', ['model' => $modelName]);
            } else {
                throw new InvalidArgumentException("Repository {$repositoryInterface} does not exist.");
            }
        }
    }

    protected function repositoryInterfaceFileExists(string $repositoryInterface): bool
    {
        // Extract the interface name from the fully qualified class name
        $interfaceName = class_basename($repositoryInterface);
        $interfacePath = app_path("Repositories/Contracts/{$interfaceName}.php");

        return file_exists($interfacePath);
    }

    protected function qualifyRepositoryInterface(string $modelName): string
    {
        $interfaceName = $modelName.'RepositoryInterface';
        $rootNamespace = $this->rootNamespace();

        return $rootNamespace.'Repositories\\Contracts\\'.$interfaceName;
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model name for the cache class'],
            ['primary_key', InputArgument::OPTIONAL, 'The primary key field name (default: Id)'],
            ['name', InputArgument::OPTIONAL, 'The name of the cache class'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['type', 't', InputOption::VALUE_OPTIONAL, 'The type of the primary key (default: int)', 'int'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the cache already exists'],
        ];
    }
}
