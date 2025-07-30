<?php

namespace App\CustomModelGenerator\Console;

use App\CustomModelGenerator\Services\DatabaseColumnReaderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Laravel\Prompts\info;

class CustomMigrationMakeCommand extends Command
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
     * The database column reader service.
     */
    protected DatabaseColumnReaderService $columnReader;

    /**
     * Create a new command instance.
     */
    public function __construct(DatabaseColumnReaderService $columnReader)
    {
        parent::__construct();
        $this->columnReader = $columnReader;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->argument('model');
        $table = Str::snake(Str::pluralStudly($modelName));

        // Create the migrations directory if it doesn't exist
        $migrationsDir = database_path('migrations');
        if (! File::exists($migrationsDir)) {
            File::makeDirectory($migrationsDir, 0755, true);
        }

        // Generate migration content
        $migrationContent = $this->generateCustomMigrationContent($table);

        // Generate migration filename with timestamp
        $timestamp = date('Y_m_d_His');
        $migrationName = 'create_'.$table.'_table';
        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");

        // Write the migration file
        File::put($migrationPath, $migrationContent);

        $this->components->info(sprintf('%s [%s] created successfully.', 'Migration', $migrationPath));

        return 0;
    }

    /**
     * Generate custom migration content
     */
    protected function generateCustomMigrationContent(string $table): string
    {
        // Read the enhanced migration stub file
        $stubPath = $this->getCustomMigrationStubPath();
        $stub = File::get($stubPath);

        // Generate column definitions
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

        $columnsString = implode("\n", $columnDefinitions);

        // Replace placeholders
        $replacements = [
            '{{ table }}' => $table,
            '{{ columnDefinitions }}' => $columnsString,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Parse columns from the columns option or read from database table
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

        // If no columns provided, try to read from existing database table
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
     * Get custom stub path for migration
     */
    protected function getCustomMigrationStubPath(): string
    {
        $customPath = app_path('CustomModelGenerator/stubs/migration.enhanced.stub');
        if (File::exists($customPath)) {
            return $customPath;
        }

        throw new \RuntimeException('Enhanced migration stub file not found at: '.$customPath);
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
            ['columns', null, InputOption::VALUE_OPTIONAL, 'JSON string of column definitions'],
            ['soft-deletes', null, InputOption::VALUE_NONE, 'Add soft deletes to the migration'],
            ['no-timestamps', null, InputOption::VALUE_NONE, 'Disable timestamps on the migration'],
        ];
    }
}
