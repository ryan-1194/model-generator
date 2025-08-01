<?php

namespace App\CustomGenerator\Console;

use App\CustomGenerator\Services\DatabaseColumnReaderService;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:custom-request')]
class CustomFormRequestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:custom-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new form request class with enhanced validation rules';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Request';

    /**
     * The database column reader service.
     */
    protected DatabaseColumnReaderService $columnReader;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files, DatabaseColumnReaderService $columnReader)
    {
        parent::__construct($files);
        $this->columnReader = $columnReader;
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/request.enhanced.stub');
    }

    /**
     * Resolve the fully qualified path to the stub.
     *
     * @param  string  $stub
     */
    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath('app/CustomGenerator'.$stub))
            ? $customPath
            : $this->laravel->basePath(trim($stub, '/'));
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Requests';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        // Get the base stub content from the parent
        $stub = parent::buildClass($name);

        // Generate validation rules based on columns
        $validationRules = $this->generateValidationRules();

        // Replace validation rules placeholder
        $stub = $this->replaceValidationRules($stub, $validationRules);

        return $stub;
    }

    /**
     * Generate validation rules based on columns
     */
    protected function generateValidationRules(): string
    {
        $rules = [];
        $columns = $this->parseColumnsFromOption();

        foreach ($columns as $column) {
            if (! $column['is_fillable']) {
                continue;
            }

            $rule = [];

            // Add required rule if not nullable
            if (! $column['nullable']) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            // Add data-type-specific rules
            switch ($column['data_type']) {
                case 'string':
                    $rule[] = 'string';
                    $rule[] = 'max:255';
                    break;
                case 'text':
                    $rule[] = 'string';
                    break;
                case 'integer':
                case 'bigInteger':
                    $rule[] = 'integer';
                    break;
                case 'boolean':
                    $rule[] = 'boolean';
                    break;
                case 'timestamp':
                case 'datetime':
                case 'date':
                    $rule[] = 'date';
                    break;
                case 'decimal':
                case 'float':
                    $rule[] = 'numeric';
                    break;
                case 'json':
                    $rule[] = 'array';
                    break;
            }

            // Add a unique rule if specified
            if ($column['unique']) {
                // Extract model name from request class name
                $requestName = class_basename($this->getNameInput());
                $modelName = str_replace(['Store', 'Update', 'Request'], '', $requestName);
                $tableName = Str::snake(Str::pluralStudly($modelName));
                $rule[] = "unique:{$tableName},{$column['column_name']}";
            }

            if (! empty($rule)) {
                $rules[] = "\t\t\t'{$column['column_name']}' => '".implode('|', $rule)."',";
            }
        }

        return implode("\n", $rules);
    }

    /**
     * Replace the validation rules for the given stub.
     *
     * @param string $stub
     * @param string $validationRules
     * @return string
     */
    protected function replaceValidationRules(string $stub, string $validationRules): string
    {
        return str_replace('{{ validationRules }}', $validationRules, $stub);
    }

    /**
     * Parse columns from the columns option or read from database table using model option
     */
    protected function parseColumnsFromOption(): array
    {
        $columnsJson = $this->option('columns');
        $modelName = $this->option('model');

        // If columns are explicitly provided, use them
        if (! empty($columnsJson)) {
            $columns = json_decode($columnsJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON format for columns option.');

                return [];
            }

            return $columns;
        }

        // If a model option is provided, read columns from the database table
        if (! empty($modelName)) {
            $tableName = Str::snake(Str::pluralStudly($modelName));

            // Check if table exists
            if (! Schema::hasTable($tableName)) {
                $this->error("Table '{$tableName}' does not exist in the database.");

                return [];
            }

            info("Reading columns from table: {$tableName}");

            try {
                return $this->columnReader->getTableColumns($tableName);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return [];
            }
        }

        // If neither columns nor model is provided, return an empty array
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the request already exists'],
            ['columns', null, InputOption::VALUE_OPTIONAL, 'JSON string of column definitions'],
            ['model', null, InputOption::VALUE_OPTIONAL, 'Model name to read columns from database table'],
        ];
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $model = text(label: 'Generate resource from model?', placeholder: 'Model name(should exist in the database)');

        $input->setOption('model', $model);
    }
}
