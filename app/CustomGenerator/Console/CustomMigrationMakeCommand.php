<?php

namespace App\CustomGenerator\Console;

use App\CustomGenerator\Services\DatabaseColumnReaderService;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\info;

#[AsCommand(name: 'make:custom-migration')]
class CustomMigrationMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:custom-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom migration file with enhanced features';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Migration';

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
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/migration.enhanced.stub');
    }

    /**
     * Resolve the fully qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
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
        return 'Database\\Migrations';
    }

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $modelName = $this->argument('model');
        $table = Str::snake(Str::pluralStudly($modelName));

        // Generate migration filename with timestamp
        $timestamp = date('Y_m_d_His');
        $migrationName = 'create_'.$table.'_table';
        $name = "{$timestamp}_{$migrationName}";

        $path = $this->getPath($name);

        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') ||
             ! $this->option('force')) &&
             $this->files->exists($path)) {
            $this->components->error($this->type.' already exists.');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->buildClass($name));

        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $path));

        return 0;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        return database_path('migrations/'.$name.'.php');
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

        // Extract table name from migration name
        $modelName = $this->argument('model');
        $table = Str::snake(Str::pluralStudly($modelName));

        // Generate column definitions based on columns
        $columnDefinitions = $this->generateColumnDefinitions();

        // Replace migration-specific placeholders
        $stub = $this->replaceTable($stub, $table);
        $stub = $this->replaceColumnDefinitions($stub, $columnDefinitions);

        return $stub;
    }

    /**
     * Generate column definitions for the migration
     */
    protected function generateColumnDefinitions(): string
    {
        $columnDefinitions = [];
        $columns = $this->parseColumnsFromOption();

        foreach ($columns as $column) {
            $definition = $this->generateColumnDefinition($column);
            $columnDefinitions[] = "            {$definition}";
        }

        if (! $this->option('no-timestamps')) {
            $columnDefinitions[] = "\t\t\t\$table->timestamps();";
        }

        if ($this->option('soft-deletes')) {
            $columnDefinitions[] = "\t\t\t\$table->softDeletes();";
        }

        return implode("\n", $columnDefinitions);
    }

    /**
     * Replace the table name for the given stub.
     */
    protected function replaceTable(string $stub, string $table): string
    {
        return str_replace('{{ table }}', $table, $stub);
    }

    /**
     * Replace the column definitions for the given stub.
     */
    protected function replaceColumnDefinitions(string $stub, string $columnDefinitions): string
    {
        return str_replace('{{ columnDefinitions }}', $columnDefinitions, $stub);
    }

    /**
     * Parse columns from the column option or read from the database table
     */
    protected function parseColumnsFromOption(): array
    {
        $columnsJson = $this->option('columns');

        // If columns are explicitly provided, use them
        if (! empty($columnsJson)) {
            $columns = json_decode($columnsJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON format for columns option.');

                return [];
            }

            return $columns;
        }

        // If no columns provided, try to read from the existing database table
        $modelName = $this->argument('model');
        $tableName = Str::snake(Str::pluralStudly($modelName));

        // Check if table exists
        if (Schema::hasTable($tableName)) {
            info("No columns specified. Reading columns from existing table: {$tableName}");

            try {
                return $this->columnReader->getTableColumns($tableName);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return [];
            }
        }

        // If no columns provided and table doesn't exist, return empty array
        $this->info("No columns specified and table '{$tableName}' does not exist. Creating migration with basic structure only.");

        return [];
    }

    /**
     * Generate column definition for migration
     */
    protected function generateColumnDefinition(array $column): string
    {
        $definition = "\$table->{$column['data_type']}('{$column['column_name']}')";

        if ($column['nullable']) {
            $definition .= '->nullable()';
        }

        if ($column['unique']) {
            $definition .= '->unique()';
        }

        if (! empty($column['default_value'])) {
            $defaultValue = is_numeric($column['default_value'])
                ? $column['default_value']
                : "'{$column['default_value']}'";
            $definition .= "->default({$defaultValue})";
        }

        $definition .= ';';

        return $definition;
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of the model for which to create the migration'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the migration even if it already exists'],
            ['columns', null, InputOption::VALUE_OPTIONAL, 'JSON string of column definitions'],
            ['soft-deletes', null, InputOption::VALUE_NONE, 'Add soft deletes to the migration'],
            ['no-timestamps', null, InputOption::VALUE_NONE, 'Disable timestamps on the migration'],
        ];
    }
}
