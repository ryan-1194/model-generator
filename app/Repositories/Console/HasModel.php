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

        if (! class_exists($modelClass) && confirm("A {$modelClass} model does not exist. Do you want to generate it?")) {
            $this->call('make:model', ['name' => $modelClass]);
        }

        return array_merge($replace, [
            '{{ namespacedModel }}' => $modelClass,
            '{{MODEL}}' => class_basename($modelClass),
        ]);
    }

    protected function parseModel($model): string
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

}
