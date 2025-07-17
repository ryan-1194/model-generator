<?php

namespace App\Services;

use App\DTOs\ColumnData;
use App\DTOs\ModelGenerationData;
use App\Models\ModelDefinition;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelGeneratorService
{
    public function generate(ModelDefinition|array|null $input): array
    {
        if (! $input) {
            return [
                'success' => false,
                'message' => 'No model data provided for generation',
                'results' => [],
            ];
        }

        $data = $this->normalizeInput($input);
        $results = [];

        try {
            // Generate base files using Artisan commands
            $this->generateBaseFiles($data, $results);

            // Modify generated files with custom content
            $this->modifyGeneratedFiles($data, $results);

            return [
                'success' => true,
                'message' => 'Model generated successfully!',
                'results' => $results,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error generating model: '.$e->getMessage(),
                'results' => $results,
            ];
        }
    }

    public function generateModel(ModelDefinition $modelDefinition): array
    {
        return $this->generate($modelDefinition);
    }

    public function generateFromFormData(?array $formData): array
    {
        return $this->generate($formData);
    }

    protected function normalizeInput(ModelDefinition|array $input): ModelGenerationData
    {
        if ($input instanceof ModelDefinition) {
            return ModelGenerationData::fromModelDefinition($input);
        }

        if (empty($input['model_name'])) {
            throw new \InvalidArgumentException('Model name is required');
        }

        return ModelGenerationData::fromArray($input);
    }

    protected function generateBaseFiles(ModelGenerationData $data, array &$results): void
    {
        // Create model file directly using enhanced stub
        $this->createModelFile($data, $results);

        // Generate additional files
        $this->generateAdditionalFiles($data, $results);
    }

    protected function createModelFile(ModelGenerationData $data, array &$results): void
    {
        // Create the Models directory if it doesn't exist
        $modelsDir = app_path('Models');
        if (! File::exists($modelsDir)) {
            File::makeDirectory($modelsDir, 0755, true);
        }

        // Generate model content using the same logic as preview
        $modelContent = $this->generateModelPreview($data);

        // Write the model file
        $modelPath = app_path("Models/{$data->model_name}.php");
        File::put($modelPath, $modelContent);

        $results['model'] = $data->model_name;
        $results['model_file'] = $modelPath;
    }

    protected function createMigrationFile(ModelGenerationData $data, array &$results): void
    {
        // Create the migrations directory if it doesn't exist
        $migrationsDir = database_path('migrations');
        if (! File::exists($migrationsDir)) {
            File::makeDirectory($migrationsDir, 0755, true);
        }

        // Generate migration content using the same logic as preview
        $migrationContent = $this->generateMigrationPreview($data);

        // Generate migration filename with timestamp
        $timestamp = date('Y_m_d_His');
        $migrationName = 'create_'.$data->table_name.'_table';
        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");

        // Write the migration file
        File::put($migrationPath, $migrationContent);

        $results['migration'] = $migrationName;
        $results['migration_file'] = $migrationPath;
    }

    protected function createJsonResourceFile(ModelGenerationData $data, array &$results): void
    {
        // Create the Resources directory if it doesn't exist
        $resourcesDir = app_path('Http/Resources');
        if (! File::exists($resourcesDir)) {
            File::makeDirectory($resourcesDir, 0755, true);
        }

        // Generate resource content using the same logic as preview
        $resourceContent = $this->generateJsonResourcePreview($data);
        $resourceName = $data->getJsonResourceName();

        // Write the resource file
        $resourcePath = app_path("Http/Resources/{$resourceName}.php");
        File::put($resourcePath, $resourceContent);

        $results['json_resource'] = $resourceName;
        $results['json_resource_file'] = $resourcePath;
    }

    protected function createFormRequestFile(ModelGenerationData $data, array &$results): void
    {
        // Create the Requests directory if it doesn't exist
        $requestsDir = app_path('Http/Requests');
        if (! File::exists($requestsDir)) {
            File::makeDirectory($requestsDir, 0755, true);
        }

        // Generate request content using the same logic as preview
        $requestContent = $this->generateFormRequestPreview($data);
        $requestName = $data->getFormRequestName();

        // Write the request file
        $requestPath = app_path("Http/Requests/{$requestName}.php");
        File::put($requestPath, $requestContent);

        $results['form_request'] = $requestName;
        $results['form_request_file'] = $requestPath;
    }

    protected function generateAdditionalFiles(ModelGenerationData $data, array &$results): void
    {
        // Generate Migration directly using enhanced stub
        if ($data->generate_migration) {
            $this->createMigrationFile($data, $results);
        }

        // Generate Factory
        if ($data->generate_factory) {
            $factoryName = $data->getFactoryName();
            Artisan::call('make:factory', [
                'name' => $factoryName,
                '--model' => $data->model_name,
            ]);
            $results['factory'] = $factoryName;
        }

        // Generate Policy
        if ($data->generate_policy) {
            $policyName = $data->getPolicyName();
            Artisan::call('make:policy', [
                'name' => $policyName,
                '--model' => $data->model_name,
            ]);
            $results['policy'] = $policyName;
        }

        // Generate JSON Resource directly using enhanced stub
        if ($data->generate_json_resource) {
            $this->createJsonResourceFile($data, $results);
        }

        // Generate Resource Controller
        if ($data->generate_resource_controller) {
            $controllerName = $data->getResourceControllerName();
            Artisan::call('make:controller', [
                'name' => $controllerName,
                '--resource' => true,
                '--model' => $data->model_name,
            ]);
            $results['resource_controller'] = $controllerName;
        }

        // Generate API Controller
        if ($data->generate_api_controller) {
            $controllerName = $data->getApiControllerName();
            Artisan::call('make:controller', [
                'name' => 'Api/'.$controllerName,
                '--api' => true,
                '--model' => $data->model_name,
            ]);
            $results['api_controller'] = $controllerName;
        }

        // Generate Form Request directly using enhanced stub
        if ($data->generate_form_request) {
            $this->createFormRequestFile($data, $results);
        }

        // Generate Repository
        if ($data->generate_repository) {
            $repositoryName = $data->getRepositoryName();
            $repositoryInterfaceName = $data->getRepositoryInterfaceName();

            Artisan::call('make:repository', [
                'model' => $data->model_name,
                'name' => $repositoryName,
                'interface' => $repositoryInterfaceName,
            ]);
            $results['repository'] = $repositoryName;
            $results['repository_interface'] = $repositoryInterfaceName;
        }
    }

    protected function modifyGeneratedFiles(ModelGenerationData $data, array &$results): void
    {
        // Migration, JSON Resource, and Form Request files are now generated directly
        // with the correct content using enhanced stubs, so no modification is needed.

        // This method is kept for potential future use with other file types
        // that might still need post-generation modification.
    }

    protected function modifyModelFile(ModelGenerationData $data, array &$results): void
    {
        $modelPath = app_path("Models/{$data->model_name}.php");

        if (! File::exists($modelPath)) {
            throw new \Exception("Model file not found: {$modelPath}");
        }

        // Generate the exact same content as the preview
        $newContent = $this->generateModelPreview($data);

        File::put($modelPath, $newContent);
        $results['model_file'] = $modelPath;
    }

    protected function generateColumnDefinition(ColumnData $column): string
    {
        $definition = "\$table->{$column->data_type}('{$column->column_name}')";

        if ($column->nullable) {
            $definition .= '->nullable()';
        }

        if ($column->unique) {
            $definition .= '->unique()';
        }

        if ($column->default_value) {
            $definition .= "->default('{$column->default_value}')";
        }

        $definition .= ';';

        return $definition;
    }

    public function preview(ModelDefinition|array|null $input): array
    {
        if (! $input) {
            return [
                'model_preview' => '// No model data provided',
                'migration_preview' => '// No migration data provided',
            ];
        }

        $data = $this->normalizeInput($input);

        $previews = [
            'model_preview' => $this->generateModelPreview($data),
        ];

        if ($data->generate_migration) {
            $previews['migration_preview'] = $this->generateMigrationPreview($data);
        }

        if ($data->generate_json_resource) {
            $previews['json_resource_preview'] = $this->generateJsonResourcePreview($data);
        }

        if ($data->generate_api_controller) {
            $previews['api_controller_preview'] = $this->generateApiControllerPreview($data);
        }

        if ($data->generate_form_request) {
            $previews['form_request_preview'] = $this->generateFormRequestPreview($data);
        }

        if ($data->generate_repository) {
            $previews['repository_preview'] = $this->generateRepositoryPreview($data);
            $previews['repository_interface_preview'] = $this->generateRepositoryInterfacePreview($data);
        }

        if ($data->generate_factory) {
            $previews['factory_preview'] = $this->generateFactoryPreview($data);
        }

        if ($data->generate_policy) {
            $previews['policy_preview'] = $this->generatePolicyPreview($data);
        }

        return $previews;
    }

    public function previewModel(ModelDefinition $modelDefinition): array
    {
        return $this->preview($modelDefinition);
    }

    public function previewFromFormData(?array $formData): array
    {
        return $this->preview($formData);
    }

    protected function generateModelPreview(ModelGenerationData $data): string
    {
        // Read the enhanced model stub file
        $stubPath = base_path('stubs/model.enhanced.stub');
        $stub = File::get($stubPath);
        $replacements = [
            '{{ namespace }}' => 'App\\Models;',
            '{{ class }}' => $data->model_name,
        ];

        // Handle trait imports
        $imports = [];
        if ($data->generate_factory) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;';
        }
        if ($data->has_soft_deletes) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\SoftDeletes;';
        }

        if (! empty($imports)) {
            $replacements['{{ imports }}'] = implode("\n", $imports);
        } else {
            $replacements["{{ imports }}\n"] = '';
        }

        // Handle trait usage - combine into single use statement
        $traits = [];
        if ($data->generate_factory) {
            $traits[] = 'HasFactory';
        }
        if ($data->has_soft_deletes) {
            $traits[] = 'SoftDeletes';
        }

        if (! empty($traits)) {
            $replacements['{{ traits }}'] = 'use '.implode(", \n\t\t", $traits).";\n";
        } else {
            $replacements['{{ traits }}'] = "//\n";
        }

        // Generate fillable array
        $fillableColumns = $data->getFillableColumns()
            ->pluck('column_name')
            ->toArray();

        if (! empty($fillableColumns)) {
            $replacements['{{ fillableArray }}'] = "protected \$fillable = [\n\t\t'".implode("',\n\t\t'", $fillableColumns)."',\n\t];\n";
        } else {
            $replacements['{{ fillableArray }}'] = '';
            $replacements["{{ fillableArray }}\n"] = '';
            $replacements["{{ fillableArray }}\r\n"] = '';
        }

        // Generate casts array
        $casts = [];
        foreach ($data->getNonIdColumns() as $column) {
            $castType = $this->getCastTypeFromDataType($column->data_type);
            if ($castType) {
                $casts[] = "'{$column->column_name}' => '{$castType}'";
            }
        }

        if (! empty($casts)) {
            $replacements['{{ castsArray }}'] = "protected \$casts = [\n\t\t".implode(",\n\t\t", $casts)."\n\t];\n";
        } else {
            $replacements['{{ castsArray }}'] = '';
            $replacements["{{ castsArray }}\n"] = '';
            $replacements["{{ castsArray }}\r\n"] = '';
        }

        // Generate timestamps property
        if (! $data->has_timestamps) {
            $replacements['{{ timestampsProperty }}'] = "public \$timestamps = false;\n";
        } else {
            $replacements['{{ timestampsProperty }}'] = '';
            $replacements["{{ timestampsProperty }}\n"] = '';
            $replacements["{{ timestampsProperty }}\r\n"] = '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generateMigrationPreview(ModelGenerationData $data): string
    {
        // Read the enhanced migration stub file
        $stubPath = base_path('stubs/migration.enhanced.stub');
        $stub = File::get($stubPath);

        // Generate column definitions
        $columnDefinitions = [];
        foreach ($data->getNonIdColumns() as $column) {
            $definition = $this->generateColumnDefinition($column);
            $columnDefinitions[] = "            {$definition}";
        }

        if ($data->has_timestamps) {
            $columnDefinitions[] = "\t\t\t\$table->timestamps();";
        }

        if ($data->has_soft_deletes) {
            $columnDefinitions[] = "\t\t\t\$table->softDeletes();";
        }

        $columnsString = implode("\n", $columnDefinitions);

        // Replace placeholders with actual values
        $replacements = [
            '{{ table }}' => $data->table_name,
            '{{ columnDefinitions }}' => $columnsString,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generateJsonResourcePreview(ModelGenerationData $data): string
    {
        // Read the enhanced resource stub file
        $stubPath = base_path('stubs/resource.enhanced.stub');
        $stub = File::get($stubPath);

        $resourceName = $data->getJsonResourceName();

        // Generate array of columns for the resource
        $resourceFields = [];
        $resourceFields[] = "\t\t\t'id' => \$this->id,";

        foreach ($data->getNonIdColumns() as $column) {
            $resourceFields[] = "\t\t\t'{$column->column_name}' => \$this->{$column->column_name},";
        }

        if ($data->has_timestamps) {
            $resourceFields[] = "\t\t\t'created_at' => \$this->created_at,";
            $resourceFields[] = "\t\t\t'updated_at' => \$this->updated_at,";
        }

        if ($data->has_soft_deletes) {
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

    protected function generateApiControllerPreview(ModelGenerationData $data): string
    {
        $controllerName = $data->getApiControllerName();
        $modelVariable = Str::camel($data->model_name);

        // Read the Laravel stub file
        $stubPath = base_path('stubs/controller.model.api.stub');
        $stub = File::get($stubPath);

        // Replace placeholders with actual values
        $replacements = [
            '{{ namespace }}' => 'App\\Http\\Controllers\\Api',
            '{{ namespacedModel }}' => 'App\\Models\\'.$data->model_name,
            '{{ rootNamespace }}' => 'App\\',
            '{{ namespacedRequests }}' => 'Illuminate\\Http\\Request;',
            '{{ class }}' => $controllerName,
            '{{ model }}' => $data->model_name,
            '{{ modelVariable }}' => $modelVariable,
            '{{ storeRequest }}' => 'Request',
            '{{ updateRequest }}' => 'Request',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generateFormRequestPreview(ModelGenerationData $data): string
    {
        // Read the enhanced request stub file
        $stubPath = base_path('stubs/request.enhanced.stub');
        $stub = File::get($stubPath);

        $requestName = $data->getFormRequestName();

        // Generate validation rules based on columns
        $rules = [];
        foreach ($data->getFillableColumns() as $column) {
            $rule = [];

            // Add required rule if not nullable
            if (! $column->nullable) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            // Add data type specific rules
            switch ($column->data_type) {
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
            if ($column->unique) {
                $rule[] = "unique:{$data->table_name},{$column->column_name}";
            }

            if (! empty($rule)) {
                $rules[] = "\t\t\t'{$column->column_name}' => '".implode('|', $rule)."',";
            }
        }
        $rulesString = implode("\n", $rules);

        // Replace placeholders with actual values
        $replacements = [
            '{{ namespace }}' => 'App\\Http\\Requests',
            '{{ class }}' => $requestName,
            '{{ validationRules }}' => $rulesString,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generateRepositoryPreview(ModelGenerationData $data): string
    {
        $repositoryName = $data->getRepositoryName();
        $repositoryInterfaceName = $data->getRepositoryInterfaceName();

        return "<?php\n\nnamespace App\\Repositories;\n\nuse App\\Models\\{$data->model_name};\nuse App\\Repositories\\Contracts\\{$repositoryInterfaceName};\n\nclass {$repositoryName} extends BaseRepository implements {$repositoryInterfaceName}\n{\n    public function __construct()\n    {\n        parent::__construct(app({$data->model_name}::class));\n    }\n}";
    }

    protected function generateRepositoryInterfacePreview(ModelGenerationData $data): string
    {
        $repositoryName = $data->getRepositoryName();
        $repositoryInterfaceName = $data->getRepositoryInterfaceName();

        return "<?php\n\nnamespace App\\Repositories\\Contracts;\n\nuse App\\Models\\{$data->model_name};\n\n/**\n * @method {$data->model_name}|null find(mixed \$id)\n * @method {$data->model_name}|null first()\n */\ninterface {$repositoryInterfaceName} extends RepositoryInterface\n{\n\t//define set of methods that {$repositoryInterfaceName} Repository must implement\n}";
    }

    protected function generateFactoryPreview(ModelGenerationData $data): string
    {
        // Read the factory stub file
        $stubPath = base_path('stubs/factory.stub');
        $stub = File::get($stubPath);

        $factoryName = $data->getFactoryName();

        // Replace placeholders with actual values
        $replacements = [
            '{{ factoryNamespace }}' => 'Database\\Factories',
            '{{ namespacedModel }}' => 'App\\Models\\'.$data->model_name,
            '{{ factory }}' => $data->model_name,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generatePolicyPreview(ModelGenerationData $data): string
    {
        // Read the policy stub file
        $stubPath = base_path('stubs/policy.stub');
        $stub = File::get($stubPath);

        $policyName = $data->getPolicyName();
        $modelVariable = Str::camel($data->model_name);

        // Replace placeholders with actual values
        $replacements = [
            '{{ namespace }}' => 'App\\Policies',
            '{{ namespacedModel }}' => 'App\\Models\\'.$data->model_name,
            '{{ namespacedUserModel }}' => 'App\\Models\\User',
            '{{ class }}' => $policyName,
            '{{ user }}' => 'User',
            '{{ model }}' => $data->model_name,
            '{{ modelVariable }}' => $modelVariable,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Get the appropriate cast type based on the data type
     */
    protected function getCastTypeFromDataType(string $dataType): ?string
    {
        return match ($dataType) {
            'string', 'text' => 'string',
            'integer', 'bigInteger' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'decimal', 'float' => 'decimal:2',
            'json' => 'array',
            default => null,
        };
    }
}
