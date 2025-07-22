<?php

namespace App\CustomModelGenerator\Console;

use App\Services\TypeMappingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;

class CustomModelFromTableCommand extends CustomModelMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:custom-model-from-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom model with enhanced features from existing database table';

    /**
     * Cached columns to avoid multiple prompts
     *
     * @var array|null
     */
    protected $cachedColumns = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the model name
        $modelName = $this->getNameInput();

        // Check if model name is reserved
        if ($this->isReservedName($modelName)) {
            $this->error('The name "'.$modelName.'" is reserved by PHP.');

            return false;
        }

        // Check if the class already exists
        if ((! $this->hasOption('force') ||
             ! $this->option('force')) &&
             $this->alreadyExists($modelName)) {
            $this->error($this->type.' already exists!');

            return false;
        }

        // Get table name - either from option or derive from model name
        //        $tableName = $this->option('table') ?: Str::snake(Str::pluralStudly($modelName));
        $tableName = Str::snake(Str::pluralStudly($modelName));

        // Check if table exists
        if (! Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist in the database.");

            return false;
        }

        info("Reading columns from table: {$tableName}");

        // Read columns from database table
        $this->cachedColumns = $this->getTableColumns($tableName);

        if (empty($this->cachedColumns)) {
            $this->error("No columns found in table '{$tableName}' or error reading table structure.");

            return false;
        }

        info('Found '.count($this->cachedColumns)." columns in table '{$tableName}'");

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
            $this->input->setOption('requests', true);
            $this->input->setOption('repository', true);
            $this->input->setOption('json-resource', true);
        }

        // Create custom model file with enhanced features
        $this->createCustomModelFile();

        // Handle other options that parent would normally handle
        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        }

        if ($this->option('requests')) {
            $this->createCustomFormRequests();
        }

        if ($this->option('repository')) {
            $this->createRepository();
        }

        if ($this->option('json-resource')) {
            $this->createJsonResource();
        }

        $this->info('Custom model created successfully with columns from database table!');

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

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        (new Collection(multiselect('Would you like any of the following?', [
            'seed' => 'Database Seeder',
            'factory' => 'Factory',
            'requests' => 'Form Requests',
            'policy' => 'Policy',
            'resource' => 'Resource Controller',
            'repository' => 'Repository',
            'json-resource' => 'JSON Resource',
            'soft-deletes' => 'Soft Deletes',
        ])))->each(fn ($option) => $input->setOption($option, true));
    }
}
