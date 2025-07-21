<?php

namespace App\CustomModelGenerator\Console;

use App\Services\TypeMappingService;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;

class CustomModelFromTableCommand extends ModelMakeCommand
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
        $tableName = $this->option('table') ?: Str::snake(Str::pluralStudly($modelName));

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
            $this->input->setOption('migration', true);
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

        // Create custom migration if requested (our custom implementation)
        if ($this->option('migration')) {
            $this->createCustomMigration();
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

    /**
     * Create custom model file with enhanced features
     */
    protected function createCustomModelFile(): void
    {
        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);

        $this->makeDirectory($path);

        $content = $this->generateCustomModelContent();
        File::put($path, $content);

        $this->info("Model created: {$path}");
    }

    /**
     * Generate custom model content with enhanced features
     */
    protected function generateCustomModelContent(): string
    {
        $stub = File::get($this->getCustomStubPath());
        $modelName = $this->getNameInput();
        $tableName = $this->option('table') ?: Str::snake(Str::pluralStudly($modelName));

        $fillableColumns = $this->parseFillableColumns();
        $castsArray = $this->generateCastsArray();

        // Build imports
        $imports = '';
        if ($this->option('soft-deletes')) {
            $imports .= "use Illuminate\Database\Eloquent\SoftDeletes;\n";
        }

        // Build traits
        $traits = '';
        if ($this->option('soft-deletes')) {
            $traits .= "    use SoftDeletes;\n\n";
        }

        // Build fillable array
        $fillableArray = "    protected \$fillable = {$fillableColumns};\n\n";

        // Build casts array
        $castsArray = "    protected \$casts = {$castsArray};\n\n";

        // Build timestamps property
        $timestampsProperty = '';
        if ($this->option('no-timestamps')) {
            $timestampsProperty = "    public \$timestamps = false;\n\n";
        }

        // Build table property
        $tableProperty = "    protected \$table = '{$tableName}';\n\n";

        $replacements = [
            '{{ namespace }}' => $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')),
            '{{ class }}' => $modelName,
            '{{ imports }}' => $imports,
            '{{ traits }}' => $traits,
            '{{ fillableArray }}' => $fillableArray,
            '{{ castsArray }}' => $castsArray,
            '{{ timestampsProperty }}' => $timestampsProperty,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Create custom migration file
     */
    protected function createCustomMigration(): void
    {
        $modelName = $this->getNameInput();
        $tableName = $this->option('table') ?: Str::snake(Str::pluralStudly($modelName));

        $migrationName = 'create_'.$tableName.'_table';
        $migrationPath = database_path('migrations/'.date('Y_m_d_His').'_'.$migrationName.'.php');

        $content = $this->generateCustomMigrationContent($tableName);
        File::put($migrationPath, $content);

        $this->info("Migration created: {$migrationPath}");
    }

    /**
     * Generate custom migration content
     */
    protected function generateCustomMigrationContent(string $table): string
    {
        $stub = File::get($this->getCustomMigrationStubPath());
        $columns = $this->parseColumnsFromOption();

        $columnDefinitions = '';
        foreach ($columns as $column) {
            $columnDefinitions .= '            '.$this->generateColumnDefinition($column)."\n";
        }

        $replacements = [
            '{{ class }}' => 'Create'.Str::studly($table).'Table',
            '{{ table }}' => $table,
            '{{ columns }}' => rtrim($columnDefinitions),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Create custom form requests
     */
    protected function createCustomFormRequests(): void
    {
        $modelName = $this->getNameInput();
        $this->createCustomFormRequest('Store'.$modelName.'Request');
        $this->createCustomFormRequest('Update'.$modelName.'Request');
    }

    /**
     * Create a custom form request
     */
    protected function createCustomFormRequest(string $requestName): void
    {
        $path = app_path('Http/Requests/'.$requestName.'.php');
        $this->makeDirectory($path);

        $content = $this->generateCustomFormRequestContent($requestName);
        File::put($path, $content);

        $this->info("Form request created: {$path}");
    }

    /**
     * Generate custom form request content
     */
    protected function generateCustomFormRequestContent(string $requestName): string
    {
        $stub = File::get($this->getCustomRequestStubPath());
        $columns = $this->parseColumnsFromOption();

        $rules = [];
        foreach ($columns as $column) {
            $columnRules = [];

            if (! $column['nullable']) {
                $columnRules[] = 'required';
            } else {
                $columnRules[] = 'nullable';
            }

            switch ($column['data_type']) {
                case 'string':
                    $columnRules[] = 'string';
                    $columnRules[] = 'max:255';
                    break;
                case 'text':
                    $columnRules[] = 'string';
                    break;
                case 'integer':
                case 'bigInteger':
                    $columnRules[] = 'integer';
                    break;
                case 'boolean':
                    $columnRules[] = 'boolean';
                    break;
                case 'date':
                    $columnRules[] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                    $columnRules[] = 'date';
                    break;
                case 'decimal':
                case 'float':
                    $columnRules[] = 'numeric';
                    break;
                case 'json':
                    $columnRules[] = 'array';
                    break;
            }

            if ($column['unique']) {
                $tableName = $this->option('table') ?: Str::snake(Str::pluralStudly($this->getNameInput()));
                $columnRules[] = "unique:{$tableName},{$column['column_name']}";
            }

            $rules[] = "            '{$column['column_name']}' => '".implode('|', $columnRules)."',";
        }

        $rulesString = implode("\n", $rules);

        $replacements = [
            '{{ namespace }}' => 'App\Http\Requests',
            '{{ class }}' => $requestName,
            '{{ validationRules }}' => $rulesString,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
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
     * Create repository for the model
     */
    protected function createRepository(): void
    {
        $modelName = $this->getNameInput();
        $repositoryName = $modelName.'Repository';
        $interfaceName = $modelName.'RepositoryInterface';

        // Create interface
        $interfacePath = app_path('Repositories/'.$interfaceName.'.php');
        $this->makeDirectory($interfacePath);

        $interfaceContent = "<?php\n\nnamespace App\\Repositories;\n\ninterface {$interfaceName}\n{\n    //\n}\n";
        File::put($interfacePath, $interfaceContent);

        // Create repository
        $repositoryPath = app_path('Repositories/'.$repositoryName.'.php');
        $repositoryContent = "<?php\n\nnamespace App\\Repositories;\n\nuse App\\Models\\{$modelName};\n\nclass {$repositoryName} implements {$interfaceName}\n{\n    protected \$model;\n\n    public function __construct({$modelName} \$model)\n    {\n        \$this->model = \$model;\n    }\n\n    //\n}\n";
        File::put($repositoryPath, $repositoryContent);

        $this->info("Repository created: {$repositoryPath}");
        $this->info("Repository interface created: {$interfacePath}");
    }

    /**
     * Create JSON resource for the model
     */
    protected function createJsonResource(): void
    {
        $modelName = $this->getNameInput();
        $resourceName = $modelName.'Resource';
        $resourcePath = app_path('Http/Resources/'.$resourceName.'.php');

        $this->makeDirectory($resourcePath);

        $content = $this->generateCustomJsonResourceContent($resourceName);
        File::put($resourcePath, $content);

        $this->info("JSON resource created: {$resourcePath}");
    }

    /**
     * Generate custom JSON resource content
     */
    protected function generateCustomJsonResourceContent(string $resourceName): string
    {
        $stub = File::get($this->getCustomResourceStubPath());
        $columns = $this->parseColumnsFromOption();

        $attributes = [];
        foreach ($columns as $column) {
            $attributes[] = "            '{$column['column_name']}' => \$this->{$column['column_name']},";
        }

        $attributesString = implode("\n", $attributes);

        $replacements = [
            '{{ namespace }}' => 'App\Http\Resources',
            '{{ class }}' => $resourceName,
            '{{ attributes }}' => $attributesString,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Get custom stub path for JSON resource
     */
    protected function getCustomResourceStubPath(): string
    {
        $customPath = app_path('CustomModelGenerator/stubs/resource.enhanced.stub');
        if (File::exists($customPath)) {
            return $customPath;
        }

        throw new \RuntimeException('Enhanced resource stub file not found at: '.$customPath);
    }

    /**
     * Parse fillable columns from cached columns
     */
    protected function parseFillableColumns(): string
    {
        $columns = $this->parseColumnsFromOption();
        $fillableColumns = array_filter($columns, fn ($column) => $column['is_fillable']);
        $fillableNames = array_map(fn ($column) => "'{$column['column_name']}'", $fillableColumns);

        return '['.implode(', ', $fillableNames).']';
    }

    /**
     * Generate casts array based on column data types
     */
    protected function generateCastsArray(): string
    {
        $columns = $this->parseColumnsFromOption();
        $casts = [];

        foreach ($columns as $column) {
            $castType = TypeMappingService::getCastTypeFromDataType($column['data_type']);
            if ($castType) {
                $casts[] = "'{$column['column_name']}' => '{$castType}'";
            }
        }

        if (empty($casts)) {
            return '[]';
        }

        return "[\n        ".implode(",\n        ", $casts)."\n    ]";
    }

    /**
     * Parse columns from cached columns (from database)
     */
    protected function parseColumnsFromOption(): array
    {
        return $this->cachedColumns ?? [];
    }

    /**
     * Get custom stub path for model
     */
    protected function getCustomStubPath(): string
    {
        $customPath = app_path('CustomModelGenerator/stubs/model.enhanced.stub');
        if (File::exists($customPath)) {
            return $customPath;
        }

        throw new \RuntimeException('Enhanced model stub file not found at: '.$customPath);
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
     * Get custom stub path for request
     */
    protected function getCustomRequestStubPath(): string
    {
        $customPath = app_path('CustomModelGenerator/stubs/request.enhanced.stub');
        if (File::exists($customPath)) {
            return $customPath;
        }

        throw new \RuntimeException('Enhanced request stub file not found at: '.$customPath);
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['table', null, InputOption::VALUE_OPTIONAL, 'The name of the database table to read columns from'],
            ['soft-deletes', null, InputOption::VALUE_NONE, 'Add soft deletes to the model'],
            ['no-timestamps', null, InputOption::VALUE_NONE, 'Disable timestamps on the model'],
            ['repository', null, InputOption::VALUE_NONE, 'Create a repository for the model'],
            ['json-resource', null, InputOption::VALUE_NONE, 'Create a JSON resource for the model'],
        ]);
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
            'migration' => 'Migration',
            'policy' => 'Policy',
            'resource' => 'Resource Controller',
            'repository' => 'Repository',
            'json-resource' => 'JSON Resource',
        ])))->each(fn ($option) => $input->setOption($option, true));
    }
}
