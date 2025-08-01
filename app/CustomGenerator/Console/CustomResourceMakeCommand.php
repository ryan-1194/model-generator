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

#[AsCommand(name: 'make:custom-resource')]
class CustomResourceMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:custom-resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource class with enhanced field generation';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

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
        return $this->resolveStubPath('/stubs/resource.enhanced.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
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
        return $rootNamespace.'\Http\Resources';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        // Get the base stub content from the parent
        $stub = parent::buildClass($name);

        // Generate resource fields based on columns
        $resourceFields = $this->generateResourceFields();

        // Replace resource fields placeholder
        return $this->replaceResourceFields($stub, $resourceFields);
    }

    /**
     * Generate resource fields based on columns
     */
    protected function generateResourceFields(): string
    {
        $resourceFields = [];
        $resourceFields[] = "\t\t\t'id' => \$this->id,";

        $columns = $this->parseColumnsFromOption();
        foreach ($columns as $column) {
            $resourceFields[] = "\t\t\t'{$column['column_name']}' => \$this->{$column['column_name']},";
        }

        // Add timestamps if not disabled
        if (! $this->option('no-timestamps')) {
            $resourceFields[] = "\t\t\t'created_at' => \$this->created_at,";
            $resourceFields[] = "\t\t\t'updated_at' => \$this->updated_at,";
        }

        // Add soft deletes if enabled
        if ($this->option('soft-deletes')) {
            $resourceFields[] = "\t\t\t'deleted_at' => \$this->deleted_at,";
        }

        return implode("\n", $resourceFields);
    }

    /**
     * Replace the resource fields for the given stub.
     *
     * @param  string  $stub
     * @param  string  $resourceFields
     * @return string
     */
    protected function replaceResourceFields($stub, $resourceFields): string
    {
        return str_replace('{{ resourceFields }}', $resourceFields, $stub);
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

        // If model option is provided, read columns from database table
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

        // If neither columns nor model is provided, return empty array
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
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the resource already exists'],
            ['columns', null, InputOption::VALUE_OPTIONAL, 'JSON string of column definitions'],
            ['model', null, InputOption::VALUE_OPTIONAL, 'Model name to read columns from database table'],
            ['soft-deletes', null, InputOption::VALUE_NONE, 'Include soft deletes field in the resource'],
            ['no-timestamps', null, InputOption::VALUE_NONE, 'Exclude timestamps from the resource'],
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
