<?php

namespace App\DTOs;

class ColumnData
{
    public function __construct(
        public string $column_name,
        public string $data_type,
        public bool $nullable = false,
        public bool $unique = false,
        public ?string $default_value = null,
        public bool $is_fillable = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            column_name: $data['column_name'],
            data_type: $data['data_type'],
            nullable: $data['nullable'] ?? false,
            unique: $data['unique'] ?? false,
            default_value: $data['default_value'] ?? null,
            is_fillable: $data['is_fillable'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'column_name' => $this->column_name,
            'data_type' => $this->data_type,
            'nullable' => $this->nullable,
            'unique' => $this->unique,
            'default_value' => $this->default_value,
            'is_fillable' => $this->is_fillable,
        ];
    }
}
