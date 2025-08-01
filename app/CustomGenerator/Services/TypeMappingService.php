<?php

namespace App\CustomGenerator\Services;

class TypeMappingService
{
    /**
     * Map database column types to Laravel migration types
     */
    public static function mapDatabaseTypeToLaravel(string $databaseType): string
    {
        $typeMap = [
            'varchar' => 'string',
            'char' => 'string',
            'text' => 'text',
            'longtext' => 'text',
            'mediumtext' => 'text',
            'tinytext' => 'text',
            'int' => 'integer',
            'integer' => 'integer',
            'tinyint' => 'boolean',
            'smallint' => 'integer',
            'mediumint' => 'integer',
            'bigint' => 'bigInteger',
            'decimal' => 'decimal',
            'numeric' => 'decimal',
            'float' => 'float',
            'double' => 'float',
            'real' => 'float',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'year' => 'integer',
            'json' => 'json',
            'boolean' => 'boolean',
            'bool' => 'boolean',
        ];

        // Remove size specifications (e.g., varchar(255) -> varchar)
        $cleanType = preg_replace('/\([^)]*\)/', '', strtolower($databaseType));

        return $typeMap[$cleanType] ?? 'string';
    }

    /**
     * Get the appropriate cast type based on the Laravel migration data type
     */
    public static function getCastTypeFromDataType(string $dataType): ?string
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

    /**
     * Get available Laravel migration data types with descriptions for interactive selection
     */
    public static function getDataTypeOptions(): array
    {
        return [
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
        ];
    }

    /**
     * Get all available Laravel migration data types as a simple array
     */
    public static function getDataTypes(): array
    {
        return array_keys(self::getDataTypeOptions());
    }

    /**
     * Check if a data type is valid
     */
    public static function isValidDataType(string $dataType): bool
    {
        return in_array($dataType, self::getDataTypes());
    }
}
