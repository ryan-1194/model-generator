<?php

namespace App\Repositories\Console;

use InvalidArgumentException;
use function Laravel\Prompts\confirm;

trait HasModel
{
    protected function getModelInput(): string
    {
        return $this->argument('model');
    }

    protected function buildModel(array $replace = []): array
    {
        $modelClass = $this->parseModel($this->getModelInput());

        // Check if model exists both by class_exists and by file existence
        // This handles cases where the model was just created but class_exists might not reflect it yet
        $modelExists = class_exists($modelClass) || $this->modelFileExists($modelClass);

        if (! $modelExists && confirm("A {$modelClass} model does not exist. Do you want to generate it?")) {
            $this->call('make:model', ['name' => $modelClass]);
        }

        return array_merge($replace, [
            '{{ namespacedModel }}' => $modelClass,
            '{{MODEL}}' => class_basename($modelClass),
        ]);
    }

    protected function modelFileExists(string $modelClass): bool
    {
        // Extract the model name from the fully qualified class name
        $modelName = class_basename($modelClass);
        $modelPath = app_path("Models/{$modelName}.php");

        return file_exists($modelPath);
    }

    protected function parseModel($model): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

}
