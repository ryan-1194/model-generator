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
        // Generate model with related files
        Artisan::call('make:model', [
            'name' => $data->model_name,
            '--migration' => $data->generate_migration,
            '--factory' => $data->generate_factory,
            '--policy' => $data->generate_policy,
        ]);

        $results['artisan_output'] = Artisan::output();

        // Generate additional files
        $this->generateAdditionalFiles($data, $results);
    }

    protected function generateAdditionalFiles(ModelGenerationData $data, array &$results): void
    {
        // Generate JSON Resource
        if ($data->generate_json_resource) {
            $resourceName = $data->getJsonResourceName();
            Artisan::call('make:resource', ['name' => $resourceName]);
            $results['json_resource'] = $resourceName;
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

        // Generate Form Request
        if ($data->generate_form_request) {
            $requestName = $data->getFormRequestName();
            Artisan::call('make:request', ['name' => $requestName]);
            $results['form_request'] = $requestName;
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
        // Modify model file
        $this->modifyModelFile($data, $results);

        // Modify migration file if needed
        if ($data->generate_migration) {
            $this->modifyMigrationFile($data, $results);
        }

        // Modify JSON Resource file if needed
        if ($data->generate_json_resource) {
            $this->modifyJsonResourceFile($data, $results);
        }

        // Modify Form Request file if needed
        if ($data->generate_form_request) {
            $this->modifyFormRequestFile($data, $results);
        }
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

    protected function modifyMigrationFile(ModelGenerationData $data, array &$results): void
    {
        $migrationFiles = File::glob(database_path('migrations/*_create_'.$data->table_name.'_table.php'));

        if (empty($migrationFiles)) {
            throw new \Exception("Migration file not found for table: {$data->table_name}");
        }

        $migrationPath = $migrationFiles[0];

        // Generate the exact same content as the preview
        $newContent = $this->generateMigrationPreview($data);

        File::put($migrationPath, $newContent);
        $results['migration_file'] = $migrationPath;
    }

    protected function modifyJsonResourceFile(ModelGenerationData $data, array &$results): void
    {
        $resourceName = $data->getJsonResourceName();
        $resourcePath = app_path("Http/Resources/{$resourceName}.php");

        if (! File::exists($resourcePath)) {
            throw new \Exception("JSON Resource file not found: {$resourcePath}");
        }

        // Generate the exact same content as the preview
        $newContent = $this->generateJsonResourcePreview($data);

        File::put($resourcePath, $newContent);
        $results['json_resource_file'] = $resourcePath;
    }

    protected function modifyFormRequestFile(ModelGenerationData $data, array &$results): void
    {
        $requestName = $data->getFormRequestName();
        $requestPath = app_path("Http/Requests/{$requestName}.php");

        if (! File::exists($requestPath)) {
            throw new \Exception("Form Request file not found: {$requestPath}");
        }

        // Generate the exact same content as the preview
        $newContent = $this->generateFormRequestPreview($data);

        File::put($requestPath, $newContent);
        $results['form_request_file'] = $requestPath;
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
            '{{ namespace }}' => "App\\Models;\n",
            '{{ class }}' => $data->model_name,
        ];

        if ($data->generate_factory) {
            $replacements['{{ factoryImport }}'] = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n";
            $replacements['{{ factory }}'] = "use HasFactory;\n";
        } else {
            $replacements['{{ factory }}'] = '';
            $replacements["{{ factory }}\n"] = '';
            $replacements["{{ factory }}\r\n"] = '';
            $replacements["{{ factoryImport }}\n"] = '';
            $replacements["{{ factoryImport }}\r\n"] = '';
        }

        if ($data->has_soft_deletes) {
            $replacements['{{ softDeletesImport }}'] = "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n";
            $replacements['{{ softDeletesTrait }}'] = "use SoftDeletes;\n";
        } else {
            $replacements['{{ softDeletesTrait }}'] = '';
            $replacements["{{ softDeletesTrait }}\n"] = '';
            $replacements["{{ softDeletesTrait }}\r\n"] = '';
            $replacements["{{ softDeletesImport }}\n"] = '';
            $replacements["{{ softDeletesImport }}\r\n"] = '';
        }

        // Generate fillable array
        $fillableColumns = $data->getFillableColumns()
            ->pluck('column_name')
            ->toArray();

        if (! empty($fillableColumns)) {
            $replacements['{{ fillableArray }}'] = "protected \$fillable = [\n        '".implode("',\n        '", $fillableColumns)."',\n    ];\n";
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
            $replacements['{{ castsArray }}'] = "protected \$casts = [\n        ".implode(",\n        ", $casts)."\n    ];\n";
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
            $columnDefinitions[] = '            $table->timestamps();';
        }

        if ($data->has_soft_deletes) {
            $columnDefinitions[] = '            $table->softDeletes();';
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
        $resourceFields[] = "            'id' => \$this->id,";

        foreach ($data->getNonIdColumns() as $column) {
            $resourceFields[] = "            '{$column->column_name}' => \$this->{$column->column_name},";
        }

        if ($data->has_timestamps) {
            $resourceFields[] = "            'created_at' => \$this->created_at,";
            $resourceFields[] = "            'updated_at' => \$this->updated_at,";
        }

        if ($data->has_soft_deletes) {
            $resourceFields[] = "            'deleted_at' => \$this->deleted_at,";
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
                $rules[] = "            '{$column->column_name}' => '".implode('|', $rule)."',";
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
