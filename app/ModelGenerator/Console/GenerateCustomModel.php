<?php

namespace App\ModelGenerator\Console;

use App\DTOs\ColumnData;
use App\Services\TypeMappingService;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class GenerateCustomModel extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:custom-model';
//                       {--g|migration : Generate migration file}
//                       {--f|factory : Generate factory file}
//                       {--p|policy : Generate policy file}
//                       {--r|resource-controller : Generate resource controller}
//                       {--j|json-resource : Generate JSON resource}
//                       {--t|timestamps : Include timestamps}
//                       {--s|soft-deletes : Include soft deletes}
//                       {--columns= : JSON string of columns definition}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'CustomModel';


    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
//            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
//            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        } elseif ($this->option('requests')) {
            $this->createFormRequests();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/model.enhanced.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return __DIR__.$stub;
//        dd(__DIR__.$stub);
        dd(file_exists($customPath = $this->laravel->basePath(trim($stub, '/'))));
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Models';
    }

    protected function buildClass($name): array|string
    {
        $hasSoftDeletes = $this->option('soft-deletes');
        $hasFactory = $this->option('factory');
        $hasTimestamps = $this->option('timestamps');
        $columns = $this->parseColumns();

        $replacements = [
//            '{{ namespace }}' => 'App\\Modelsfffff',
            '{{ class }}' => $this->getNameInput(),
        ];

        // Handle trait imports
        $imports = [];
        if ($hasFactory) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;';
        }
        if ($hasSoftDeletes) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\SoftDeletes;';
        }

        if (! empty($imports)) {
            $replacements['{{ imports }}'] = implode("\n", $imports);
        } else {
            $replacements["{{ imports }}\n"] = '';
        }

        // Handle trait usage - combine into single use statement
        $traits = [];
        if ($hasFactory) {
            $traits[] = 'HasFactory';
        }
        if ($hasSoftDeletes) {
            $traits[] = 'SoftDeletes';
        }

        if (! empty($traits)) {
            $replacements['{{ traits }}'] = "\tuse ".implode(", \n\t\t", $traits).";\n";
        } else {
            $replacements['{{ traits }}'] = "//\n";
        }

        // Generate fillable array
        $fillableColumns = $columns->where('is_fillable', true)
            ->pluck('column_name')
            ->toArray();

        if (! empty($fillableColumns)) {
            $replacements['{{ fillableArray }}'] = "\tprotected \$fillable = [\n\t\t'".implode("',\n\t\t'", $fillableColumns)."',\n\t];\n";
        } else {
            $replacements["{{ fillableArray }}\n"] = '';
            $replacements["{{ fillableArray }}\r\n"] = '';
        }

        // Generate casts array
        $casts = [];
        $nonIdColumns = $columns->filter(fn (ColumnData $column) => strtolower($column->column_name) !== 'id');

        foreach ($nonIdColumns as $column) {
            $castType = TypeMappingService::getCastTypeFromDataType($column->data_type);
            if ($castType) {
                $casts[] = "'{$column->column_name}' => '{$castType}'";
            }
        }

        if (! empty($casts)) {
            $replacements['{{ castsArray }}'] = "\tprotected \$casts = [\n\t\t".implode(",\n\t\t", $casts)."\n\t];\n";
        } else {
            $replacements["{{ castsArray }}\n"] = '';
            $replacements["{{ castsArray }}\r\n"] = '';
        }

        if ($hasTimestamps) {
            $replacements['{{ timestampsProperty }}'] = "\tpublic \$timestamps = false;\n";
        } else {
            $replacements["{{ timestampsProperty }}\n"] = '';
            $replacements["{{ timestampsProperty }}\r\n"] = '';
        }

//        dd(str_replace(
//            array_keys($replacements), array_values($replacements), parent::buildClass($name)
//        ));
//        dd('test');

        return str_replace(
            array_keys($replacements), array_values($replacements), parent::buildClass($name)
        );
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
            ['factory', 'r', InputOption::VALUE_NONE, 'The name of the '.strtolower($this->type)],
            ['soft-deletes', 's', InputOption::VALUE_NONE, 'The name of the '.strtolower($this->type)],
            ['timestamps', 't', InputOption::VALUE_NONE, 'The name of the '.strtolower($this->type)],
        ];
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the '.strtolower($this->type)],
            ['columns', InputArgument::OPTIONAL, 'The columns of the '.strtolower($this->type)],
        ];
    }

    protected function getRepositoryName(): string
    {
        return $this->getNameInput();
    }

    protected function parseColumns(): \Illuminate\Support\Collection
    {
        $columnsJson = $this->argument('columns');

        if ($columnsJson) {
            $columns = json_decode($columnsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error('Invalid JSON format for columns');

                return collect();
            }

            return $columns;
        }

        // Interactive column input using Laravel Prompts
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

        return collect($columns ?? [])
            ->map(fn (array $columnData) => ColumnData::fromArray($columnData));
    }

    protected function buildModel()
    {

    }
}
