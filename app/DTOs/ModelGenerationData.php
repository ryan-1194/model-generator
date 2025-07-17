<?php

namespace App\DTOs;

use App\Models\ModelDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ModelGenerationData
{
    public function __construct(
        public string $model_name,
        public ?string $table_name = null,
        public bool $generate_migration = true,
        public bool $has_timestamps = true,
        public bool $has_soft_deletes = false,
        public bool $generate_factory = true,
        public bool $generate_policy = true,
        public bool $generate_resource_controller = true,
        public bool $generate_json_resource = false,
        public bool $generate_api_controller = false,
        public bool $generate_form_request = false,
        public bool $generate_repository = false,
        public ?string $factory_name = null,
        public ?string $policy_name = null,
        public ?string $resource_controller_name = null,
        public ?string $json_resource_name = null,
        public ?string $api_controller_name = null,
        public ?string $form_request_name = null,
        public ?string $repository_name = null,
        /** @var Collection<ColumnData> */
        public Collection $columns = new Collection(),
    ) {
        // Auto-generate table name if not provided
        if (!$this->table_name) {
            $this->table_name = Str::snake(Str::plural($this->model_name));
        }
    }

    public static function fromArray(array $data): self
    {
        $columns = collect($data['columns'] ?? [])
            ->map(fn(array $columnData) => ColumnData::fromArray($columnData));

        return new self(
            model_name: $data['model_name'],
            table_name: $data['table_name'] ?? null,
            generate_migration: $data['generate_migration'] ?? true,
            has_timestamps: $data['has_timestamps'] ?? true,
            has_soft_deletes: $data['has_soft_deletes'] ?? false,
            generate_factory: $data['generate_factory'] ?? true,
            generate_policy: $data['generate_policy'] ?? true,
            generate_resource_controller: $data['generate_resource_controller'] ?? true,
            generate_json_resource: $data['generate_json_resource'] ?? false,
            generate_api_controller: $data['generate_api_controller'] ?? false,
            generate_form_request: $data['generate_form_request'] ?? false,
            generate_repository: $data['generate_repository'] ?? false,
            factory_name: $data['factory_name'] ?? null,
            policy_name: $data['policy_name'] ?? null,
            resource_controller_name: $data['resource_controller_name'] ?? null,
            json_resource_name: $data['json_resource_name'] ?? null,
            api_controller_name: $data['api_controller_name'] ?? null,
            form_request_name: $data['form_request_name'] ?? null,
            repository_name: $data['repository_name'] ?? null,
            columns: $columns,
        );
    }

    public static function fromModelDefinition(ModelDefinition $modelDefinition): self
    {
        $columns = $modelDefinition->columns->map(fn($column) => new ColumnData(
            column_name: $column->column_name,
            data_type: $column->data_type,
            nullable: $column->nullable,
            unique: $column->unique,
            default_value: $column->default_value,
            is_fillable: $column->is_fillable,
        ));

        return new self(
            model_name: $modelDefinition->model_name,
            table_name: $modelDefinition->table_name,
            generate_migration: $modelDefinition->generate_migration,
            has_timestamps: $modelDefinition->has_timestamps,
            has_soft_deletes: $modelDefinition->has_soft_deletes,
            generate_factory: $modelDefinition->generate_factory ?? true,
            generate_policy: $modelDefinition->generate_policy ?? true,
            generate_resource_controller: $modelDefinition->generate_resource_controller ?? true,
            generate_json_resource: $modelDefinition->generate_json_resource ?? false,
            generate_api_controller: $modelDefinition->generate_api_controller ?? false,
            generate_form_request: $modelDefinition->generate_form_request ?? false,
            generate_repository: $modelDefinition->generate_repository ?? false,
            factory_name: $modelDefinition->factory_name,
            policy_name: $modelDefinition->policy_name,
            resource_controller_name: $modelDefinition->resource_controller_name,
            json_resource_name: $modelDefinition->json_resource_name,
            api_controller_name: $modelDefinition->api_controller_name,
            form_request_name: $modelDefinition->form_request_name,
            repository_name: $modelDefinition->repository_name,
            columns: $columns,
        );
    }

    public function getFactoryName(): string
    {
        return $this->factory_name ?: $this->model_name . 'Factory';
    }

    public function getPolicyName(): string
    {
        return $this->policy_name ?: $this->model_name . 'Policy';
    }

    public function getResourceControllerName(): string
    {
        return $this->resource_controller_name ?: $this->model_name . 'Controller';
    }

    public function getJsonResourceName(): string
    {
        return $this->json_resource_name ?: $this->model_name . 'Resource';
    }

    public function getApiControllerName(): string
    {
        return $this->api_controller_name ?: $this->model_name . 'ApiController';
    }

    public function getFormRequestName(): string
    {
        return $this->form_request_name ?: $this->model_name . 'Request';
    }

    public function getRepositoryName(): string
    {
        return $this->repository_name ?: $this->model_name . 'Repository';
    }

    public function getRepositoryInterfaceName(): string
    {
        return $this->getRepositoryName() . 'Interface';
    }

    public function getFillableColumns(): Collection
    {
        return $this->columns->where('is_fillable', true);
    }

    public function getNonIdColumns(): Collection
    {
        return $this->columns->filter(fn(ColumnData $column) =>
            strtolower($column->column_name) !== 'id'
        );
    }
}
