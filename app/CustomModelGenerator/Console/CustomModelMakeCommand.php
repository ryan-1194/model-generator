<?php

namespace App\CustomModelGenerator\Console;

use App\Services\TypeMappingService;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CustomModelMakeCommand extends ModelMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:custom-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom model with enhanced features';

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
        // Create the model file first (skip parent to avoid default migration creation)
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "'.$this->getNameInput().'" is reserved by PHP.');

            return false;
        }

        // First check if the class already exists.
        if ((! $this->hasOption('force') ||
             ! $this->option('force')) &&
             $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        $this->cachedColumns = $this->getColumns();

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('api', true);
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

        // Create custom form requests if requested
        if ($this->option('requests')) {
            $this->createCustomFormRequests();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->repositoryCommandExists() && $this->option('repository')) {
            $this->createRepository();
        }

        if ($this->option('json-resource')) {
            $this->createJsonResource();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }

        return 0;
    }

    /**
     * Create custom model file with enhanced features
     */
    protected function createCustomModelFile(): void
    {
        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);

        // Generate model content using custom logic
        $modelContent = $this->generateCustomModelContent();

        // Write the model file
        $this->makeDirectory($path);
        $this->files->put($path, $modelContent);

        $this->components->info(sprintf('%s [%s] created successfully.', 'Model', $path));
    }

    /**
     * Generate custom model content
     */
    protected function generateCustomModelContent(): string
    {
        $modelName = $this->getNameInput();

        // Read the enhanced model stub file
        $stubPath = $this->getCustomStubPath();
        $stub = File::get($stubPath);

        $replacements = [
            '{{ namespace }}' => $this->getNamespace($this->qualifyClass($this->getNameInput())),
            '{{ class }}' => $modelName,
        ];

        // Handle trait imports
        $imports = [];
        if ($this->option('factory')) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;';
        }
        if ($this->option('soft-deletes')) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\SoftDeletes;';
        }

        if (! empty($imports)) {
            $replacements['{{ imports }}'] = implode("\n", $imports);
        } else {
            $replacements["{{ imports }}\n"] = '';
        }

        // Handle trait usage
        $traits = [];
        if ($this->option('factory')) {
            $traits[] = 'HasFactory';
        }
        if ($this->option('soft-deletes')) {
            $traits[] = 'SoftDeletes';
        }

        if (! empty($traits)) {
            $replacements['{{ traits }}'] = "\tuse ".implode(", \n\t\t", $traits).";\n";
        } else {
            $replacements['{{ traits }}'] = "\t//\n";
        }

        // Generate fillable array from columns option
        $fillableColumns = $this->parseFillableColumns();
        if (! empty($fillableColumns)) {
            $replacements['{{ fillableArray }}'] = "\tprotected \$fillable = [\n\t\t'".implode("',\n\t\t'", $fillableColumns)."',\n\t];\n";
        } else {
            $replacements['{{ fillableArray }}'] = '';
        }

        // Generate casts array
        $casts = $this->generateCastsArray();
        if (! empty($casts)) {
            $replacements['{{ castsArray }}'] = "\tprotected \$casts = [\n\t\t".implode(",\n\t\t", $casts)."\n\t];\n";
        } else {
            $replacements["{{ castsArray }}\n"] = '';
        }

        // Generate timestamps property
        if ($this->option('no-timestamps')) {
            $replacements['{{ timestampsProperty }}'] = "public \$timestamps = false;\n";
        } else {
            $replacements["{{ timestampsProperty }}\n"] = '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Create custom migration file
     */
    protected function createCustomMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

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
     * Create custom form requests
     */
    protected function createCustomFormRequests(): void
    {
        $modelName = class_basename($this->argument('name'));

        // Create Store request
        $this->createCustomFormRequest("Store{$modelName}Request");

        // Create Update request
        $this->createCustomFormRequest("Update{$modelName}Request");
    }

    /**
     * Create a single custom form request
     */
    protected function createCustomFormRequest(string $requestName): void
    {
        // Create the Requests directory if it doesn't exist
        $requestsDir = app_path('Http/Requests');
        if (! File::exists($requestsDir)) {
            File::makeDirectory($requestsDir, 0755, true);
        }

        // Generate request content
        $requestContent = $this->generateCustomFormRequestContent($requestName);

        // Write the request file
        $requestPath = app_path("Http/Requests/{$requestName}.php");
        File::put($requestPath, $requestContent);

        $this->components->info(sprintf('%s [%s] updated successfully.', 'Request', $requestPath));
    }

    /**
     * Generate custom form request content
     */
    protected function generateCustomFormRequestContent(string $requestName): string
    {
        // Read the enhanced request stub file
        $stubPath = $this->getCustomRequestStubPath();
        $stub = File::get($stubPath);

        // Generate validation rules based on columns
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

            // Add data type specific rules
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

            // Add unique rule if specified
            if ($column['unique']) {
                $tableName = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));
                $rule[] = "unique:{$tableName},{$column['column_name']}";
            }

            if (! empty($rule)) {
                $rules[] = "\t\t\t'{$column['column_name']}' => '".implode('|', $rule)."',";
            }
        }

        $rulesString = implode("\n", $rules);

        // Replace placeholders
        $replacements = [
            '{{ namespace }}' => 'App\\Http\\Requests',
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
            $defaultValue = is_string($column['default_value']) ? "'{$column['default_value']}'" : $column['default_value'];
            $definition .= "->default({$defaultValue})";
        }

        $definition .= ';';

        return $definition;
    }

    /**
     * Create a repository for the model
     */
    protected function createRepository(): void
    {
        $modelName = class_basename($this->argument('name'));
        $repositoryName = "{$modelName}Repository";
        $repositoryInterfaceName = "{$modelName}RepositoryInterface";

        // Create a repository using Artisan command
        $this->call('make:repository', [
            'model' => $modelName,
            'name' => $repositoryName,
            'interface' => $repositoryInterfaceName,
        ]);
    }

    /**
     * Create a JSON resource for the model
     */
    protected function createJsonResource(): void
    {
        $modelName = class_basename($this->argument('name'));
        $resourceName = "{$modelName}Resource";

        // Create the Resources directory if it doesn't exist
        $resourcesDir = app_path('Http/Resources');
        if (! File::exists($resourcesDir)) {
            File::makeDirectory($resourcesDir, 0755, true);
        }

        // Generate resource content using custom logic
        $resourceContent = $this->generateCustomJsonResourceContent($resourceName);

        // Write the resource file
        $resourcePath = app_path("Http/Resources/{$resourceName}.php");
        File::put($resourcePath, $resourceContent);

        $this->components->info(sprintf('%s [%s] created successfully.', 'JSON Resource', $resourceName));
    }

    /**
     * Generate custom JSON resource content
     */
    protected function generateCustomJsonResourceContent(string $resourceName): string
    {
        // Read the enhanced resource stub file
        $stubPath = $this->getCustomResourceStubPath();
        $stub = File::get($stubPath);

        // Generate array of columns for the resource
        $resourceFields = [];
        $resourceFields[] = "\t\t\t'id' => \$this->id,";

        $columns = $this->parseColumnsFromOption();
        foreach ($columns as $column) {
            $resourceFields[] = "\t\t\t'{$column['column_name']}' => \$this->{$column['column_name']},";
        }

        if (! $this->option('no-timestamps')) {
            $resourceFields[] = "\t\t\t'created_at' => \$this->created_at,";
            $resourceFields[] = "\t\t\t'updated_at' => \$this->updated_at,";
        }

        if ($this->option('soft-deletes')) {
            $resourceFields[] = "\t\t\t'deleted_at' => \$this->deleted_at,";
        }

        $fieldsString = implode("\n", $resourceFields);

        // Replace placeholders with actual values
        $replacements = [
            '{{ namespace }}' => 'App\\Http\\Resources',
            '{{ class }}' => $resourceName,
            '{{ resourceFields }}' => $fieldsString,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Get custom stub path for resource
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
     * Parse fillable columns from options
     */
    protected function parseFillableColumns(): array
    {
        $columns = $this->parseColumnsFromOption();

        return array_column(array_filter($columns, fn ($col) => $col['is_fillable']), 'column_name');
    }

    /**
     * Generate casts array
     */
    protected function generateCastsArray(): array
    {
        $casts = [];
        $columns = $this->parseColumnsFromOption();

        foreach ($columns as $column) {
            $castType = TypeMappingService::getCastTypeFromDataType($column['data_type']);
            if ($castType) {
                $casts[] = "'{$column['column_name']}' => '{$castType}'";
            }
        }

        return $casts;
    }

    /**
     * Parse columns from option or interactive input
     */
    protected function parseColumnsFromOption(): array
    {
        // Return cached columns if already parsed
        if ($this->cachedColumns !== null) {
            return $this->cachedColumns;
        }

        $columnsJson = $this->option('columns');

        if ($columnsJson) {
            $columns = json_decode($columnsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error('Invalid JSON format for columns');
                $this->cachedColumns = [];

                return [];
            }
            $this->cachedColumns = $columns;

            return $columns;
        }

        return $this->cachedColumns;
    }

    protected function getColumns()
    {
        // Interactive column input using Laravel Prompts when columns option is empty
        $columns = [];

        if (confirm('Would you like to add custom columns?', false)) {
            while (true) {
                $columnName = text(
                    label: 'Column name',
                    placeholder: 'Enter column name or leave empty to finish',
                    hint: 'Press Enter without typing to finish adding columns'
                );

                if (empty($columnName)) {
                    break;
                }

                $dataType = select(
                    label: 'Data type',
                    options: TypeMappingService::getDataTypeOptions(),
                    default: 'string'
                );

                $nullable = confirm('Should this column be nullable?', false);
                $unique = confirm('Should this column be unique?', false);
                $fillable = confirm('Should this column be fillable?', true);

                $defaultValue = text(
                    label: 'Default value',
                    placeholder: 'Leave empty for no default value',
                    required: false
                );

                $columns[] = [
                    'column_name' => $columnName,
                    'data_type' => $dataType,
                    'nullable' => $nullable,
                    'unique' => $unique,
                    'is_fillable' => $fillable,
                    'default_value' => $defaultValue ?: '',
                ];

                info("Added column: {$columnName} ({$dataType})");
            }
        }

        return $columns;
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
        $options = [
            ['columns', null, InputOption::VALUE_OPTIONAL, 'JSON string of column definitions'],
            ['soft-deletes', null, InputOption::VALUE_NONE, 'Add soft deletes to the model'],
            ['no-timestamps', null, InputOption::VALUE_NONE, 'Disable timestamps on the model'],
            ['json-resource', null, InputOption::VALUE_NONE, 'Create a JSON resource for the model'],
            ['repository', null, InputOption::VALUE_NONE, 'Create a repository for the model'],
        ];

        return array_merge(parent::getOptions(), $options);
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $options = [
            'seed' => 'Database Seeder',
            'factory' => 'Factory',
            'requests' => 'Form Requests',
            'migration' => 'Migration',
            'policy' => 'Policy',
            'api' => 'API Controller',
            'json-resource' => 'JSON Resource',
            'soft-deletes' => 'Soft Deletes',
        ];

        if ($this->repositoryCommandExists()) {
            $options['repository'] = 'Repository';
        }

        (new Collection(multiselect('Would you like any of the following?', $options)))->each(fn ($option) => $input->setOption($option, true));
    }

    protected function commandExists(string $command): bool
    {
        return collect(\Artisan::all())->has($command);
    }

    protected function repositoryCommandExists(): bool
    {
        return $this->commandExists('make:repository');
    }
}
