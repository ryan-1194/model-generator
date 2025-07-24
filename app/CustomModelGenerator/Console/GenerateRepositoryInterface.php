<?php

namespace App\CustomModelGenerator\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateRepositoryInterface extends GeneratorCommand
{
    use HasModel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:repository-interface';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository interface';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'RepositoryInterface';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/../stubs/interface.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Repositories\Contracts';
    }

    protected function buildClass($name): array|string
    {
        $replace = [
            '{{CLASS}}' => $this->getInterfaceName(),
        ];

        $replace = $this->buildModel($replace);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the repository'],
            ['model', InputArgument::REQUIRED, 'The model name associated with the repository'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the interface already exists'],
        ];
    }

    protected function getNameInput(): string
    {
        $name = parent::getNameInput();

        if (Str::endsWith($name, 'RepositoryInterface')) {
            return $name;
        }

        return $name.'RepositoryInterface';
    }

    protected function getInterfaceName(): string
    {
        return $this->getNameInput();
    }
}
