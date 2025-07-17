<?php

namespace App\Console\Commands;

use App\Services\ModelGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;

class GenerateModelCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:model
                            {name : The name of the model}
                            {--table= : The name of the database table}
                            {--migration : Generate migration file}
                            {--no-migration : Skip migration generation}
                            {--factory : Generate factory file}
                            {--no-factory : Skip factory generation}
                            {--policy : Generate policy file}
                            {--no-policy : Skip policy generation}
                            {--resource-controller : Generate resource controller}
                            {--json-resource : Generate JSON resource}
                            {--api-controller : Generate API controller}
                            {--form-request : Generate form request}
                            {--repository : Generate repository}
                            {--timestamps : Include timestamps}
                            {--no-timestamps : Skip timestamps}
                            {--soft-deletes : Include soft deletes}
                            {--columns= : JSON string of columns definition}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a model with related files using ModelGeneratorService';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = $this->argument('name');

        // Validate model name
        if (! $modelName || ! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $modelName)) {
            error('Model name must be in StudlyCase format (e.g., BlogPost)');

            return 1;
        }

        // Build form data array
        $formData = $this->buildFormData($modelName);

        $generator = new ModelGeneratorService;

        // Always show preview first
        info('Generating preview...');
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
     * Build form data array from command options
     */
    protected function buildFormData(string $modelName): array
    {
        $formData = [
            'model_name' => $modelName,
            'table_name' => $this->option('table') ?: Str::snake(Str::plural($modelName)),
            'generate_migration' => $this->shouldGenerate('migration'),
            'generate_factory' => $this->shouldGenerate('factory'),
            'generate_policy' => $this->shouldGenerate('policy'),
            'generate_resource_controller' => $this->option('resource-controller'),
            'generate_json_resource' => $this->option('json-resource'),
            'generate_api_controller' => $this->option('api-controller'),
            'generate_form_request' => $this->option('form-request'),
            'generate_repository' => $this->option('repository'),
            'has_timestamps' => $this->shouldGenerate('timestamps', true),
            'has_soft_deletes' => $this->option('soft-deletes'),
            'columns' => $this->parseColumns(),
        ];

        return $formData;
    }

    /**
     * Determine if a feature should be generated based on options
     */
    protected function shouldGenerate(string $feature, bool $default = true): bool
    {
        if ($this->option($feature)) {
            return true;
        }

        if ($this->option("no-{$feature}")) {
            return false;
        }

        return $default;
    }

    /**
     * Parse columns from JSON string or interactive input
     */
    protected function parseColumns(): array
    {
        $columnsJson = $this->option('columns');

        if ($columnsJson) {
            $columns = json_decode($columnsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error('Invalid JSON format for columns');

                return [];
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
                    options: [
                        'string' => 'String (VARCHAR)',
                        'text' => 'Text (TEXT)',
                        'integer' => 'Integer (INT)',
                        'bigInteger' => 'Big Integer (BIGINT)',
                        'boolean' => 'Boolean (TINYINT)',
                        'date' => 'Date (DATE)',
                        'datetime' => 'DateTime (DATETIME)',
                        'timestamp' => 'Timestamp (TIMESTAMP)',
                        'decimal' => 'Decimal (DECIMAL)',
                        'float' => 'Float (FLOAT)',
                        'json' => 'JSON (JSON)',
                    ],
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
