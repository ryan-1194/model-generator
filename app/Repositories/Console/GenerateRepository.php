<?php

namespace App\Repositories\Console;

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
        return $this->resolveStubPath('/stubs/repository.stub');
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
        $replace = [
            '{{CLASS}}' => $this->getRepositoryName(),
        ];

        $replace = $this->buildModel($replace);
        $replace = $this->buildInterface($replace);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
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
