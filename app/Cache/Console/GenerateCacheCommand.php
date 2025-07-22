<?php

namespace App\Cache\Console;

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
        return $this->resolveStubPath('/stubs/cache.stub');
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

    public function handle()
    {
        // Validate the model exists before doing anything else
        $modelName = $this->getModelInput();
        $this->validateModel($modelName);

        // Validate the repository exists before doing anything else
        $this->validateRepository($modelName);

        // Call the parent handle method to continue with normal flow
        return parent::handle();
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

        if (! interface_exists($repositoryInterface)) {
            if (confirm("A {$repositoryInterface} repository does not exist. Do you want to generate it?")) {
                $this->call('make:repository', ['model' => $modelName]);
            } else {
                throw new InvalidArgumentException("Repository {$repositoryInterface} does not exist.");
            }
        }
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
