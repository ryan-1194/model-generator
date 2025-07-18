<?php

namespace App\Console\Commands;

use App\Services\ModelGeneratorService;
use App\Services\TypeMappingService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class GenerateFromTableCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:from-table
                            {model : The name of the model}
                            {--table= : The name of the database table (optional, will be inferred from model name)}
                            {--a|all : Generate all available files (model, migration, factory, policy, resource controller)}
                            {--m|model : Generate model file}
                            {--g|migration : Generate migration file}
                            {--f|factory : Generate factory file}
                            {--p|policy : Generate policy file}
                            {--r|resource-controller : Generate resource controller}
                            {--j|json-resource : Generate JSON resource}
                            {--c|api-controller : Generate API controller}
                            {--x|form-request : Generate form request}
                            {--e|repository : Generate repository}
                            {--t|timestamps : Include timestamps}
                            {--s|soft-deletes : Include soft deletes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate files by introspecting an existing database table. Use --all or specific flags like -m (model), -g (migration), -f (factory), etc.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = $this->argument('model');

        // Validate model name
        if (! $modelName || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $modelName)) {
            error('Model name must be in StudlyCase format (e.g., BlogPost)');

            return 1;
        }

        // Determine table name
        $tableName = $this->option('table') ?: Str::snake(Str::plural($modelName));

        // Check if table exists
        if (! Schema::hasTable($tableName)) {
            error("Table '{$tableName}' does not exist in the database.");

            return 1;
        }

        info("Introspecting table: {$tableName}");

        // Get column information from database
        $columns = $this->getTableColumns($tableName);

        if (empty($columns)) {
            error("No columns found in table '{$tableName}'.");

            return 1;
        }

        info('Found '.count($columns)." columns in table '{$tableName}'");

        // Build form data array
        $formData = $this->buildFormData($modelName, $tableName, $columns);

        $generator = new ModelGeneratorService;

        // Always show preview first
        $this->showPreview($generator, $formData);

        // Ask for confirmation before generating files
        if (! confirm('Do you want to generate these files?', true)) {
            info('File generation cancelled.');

            return 0;
        }

        // Generate files
        info("Generating model: {$modelName}");
        $result = $generator->generateFromFormData($formData);

        if ($result['success']) {
            info($result['message']);
            $this->displayResults($result['results']);
        } else {
            error($result['message']);

            return 1;
        }

        return 0;
    }

    /**
     * Get column information from database table
     */
    protected function getTableColumns(string $tableName): array
    {
        $columns = [];

        try {
            // Get column information using Laravel's Schema facade
            $columnListing = Schema::getColumnListing($tableName);

            foreach ($columnListing as $columnName) {
                // Skip common Laravel timestamp and soft delete columns
                if (in_array($columnName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $columnType = Schema::getColumnType($tableName, $columnName);
                $columnInfo = $this->getColumnDetails($tableName, $columnName);

                $columns[] = [
                    'column_name' => $columnName,
                    'data_type' => TypeMappingService::mapDatabaseTypeToLaravel($columnType),
                    'nullable' => $columnInfo['nullable'] ?? false,
                    'unique' => $columnInfo['unique'] ?? false,
                    'is_fillable' => true, // Default to fillable, user can modify later
                    'default_value' => $columnInfo['default'] ?? '',
                ];
            }
        } catch (\Exception $e) {
            error('Error reading table structure: '.$e->getMessage());

            return [];
        }

        return $columns;
    }

    /**
     * Get detailed column information
     */
    protected function getColumnDetails(string $tableName, string $columnName): array
    {
        try {
            // Use raw database queries to get detailed column information
            $connection = DB::connection();
            $database = $connection->getDatabaseName();

            if ($connection->getDriverName() === 'mysql') {
                $result = DB::select('
                    SELECT
                        COLUMN_NAME,
                        IS_NULLABLE,
                        COLUMN_DEFAULT,
                        COLUMN_KEY
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                ', [$database, $tableName, $columnName]);

                if (! empty($result)) {
                    $column = $result[0];

                    return [
                        'nullable' => $column->IS_NULLABLE === 'YES',
                        'unique' => $column->COLUMN_KEY === 'UNI',
                        'default' => $column->COLUMN_DEFAULT,
                    ];
                }
            }

            // Fallback for other database types or if query fails
            return [
                'nullable' => false,
                'unique' => false,
                'default' => null,
            ];
        } catch (\Exception $e) {
            return [
                'nullable' => false,
                'unique' => false,
                'default' => null,
            ];
        }
    }


    /**
     * Build a form data array from an introspected table
     */
    protected function buildFormData(string $modelName, string $tableName, array $columns): array
    {
        return [
            'model_name' => $modelName,
            'table_name' => $tableName,
            'generate_model' => $this->shouldGenerate('model', false),
            'generate_migration' => $this->shouldGenerate('migration', false),
            'generate_factory' => $this->shouldGenerate('factory', false),
            'generate_policy' => $this->shouldGenerate('policy', false),
            'generate_resource_controller' => $this->shouldGenerate('resource-controller', false),
            'generate_json_resource' => $this->shouldGenerate('json-resource', false),
            'generate_api_controller' => $this->shouldGenerate('api-controller', false),
            'generate_form_request' => $this->shouldGenerate('form-request', false),
            'generate_repository' => $this->shouldGenerate('repository', false),
            'has_timestamps' => $this->shouldGenerate('timestamps', true),
            'has_soft_deletes' => $this->shouldGenerate('soft-deletes', false),
            'columns' => $columns,
        ];
    }

    /**
     * Determine if a feature should be generated based on options
     */
    protected function shouldGenerate(string $feature, bool $default = false): bool
    {
        // If --all is specified, generate all main components
        if ($this->option('all')) {
            $allComponents = ['model', 'migration', 'factory', 'policy', 'resource-controller'];
            if (in_array($feature, $allComponents)) {
                return true;
            }
        }

        // Check if specific feature flag is set
        if ($this->option($feature)) {
            return true;
        }

        return $default;
    }

    /**
     * Show preview of generated files
     */
    protected function showPreview(ModelGeneratorService $generator, array $formData): void
    {
        $this->info('Generating preview...');
        $previews = $generator->previewFromFormData($formData);

        foreach ($previews as $type => $content) {
            $title = str_replace('_', ' ', Str::title($type));
            $this->line('');
            $this->line("<fg=cyan>=== {$title} ===</>");
            $this->line($content);
        }
    }

    /**
     * Display generation results
     */
    protected function displayResults(array $results): void
    {
        $this->line('');
        $this->info('Generated files:');

        foreach ($results as $key => $value) {
            if (is_string($value) && $key !== 'artisan_output') {
                $this->line("  <fg=green>✓</> {$key}: {$value}");
            }
        }

        if (isset($results['artisan_output']) && ! empty(trim($results['artisan_output']))) {
            $this->line('');
            $this->line('<fg=yellow>Artisan Output:</fg=yellow>');
            $this->line($results['artisan_output']);
        }
    }
}
