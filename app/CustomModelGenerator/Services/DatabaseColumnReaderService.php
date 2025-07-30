<?php

namespace App\CustomModelGenerator\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseColumnReaderService
{
    /**
     * Get column information from database table
     */
    public function getTableColumns(string $tableName): array
    {
        $columns = [];

        try {
            // Get column information using Laravel's Schema facade
            $columnListing = Schema::getColumnListing($tableName);

            foreach ($columnListing as $columnName) {
                // Skip common Laravel timestamp and soft delete columns
                if (in_array($columnName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $columnType = Schema::getColumnType($tableName, $columnName);
                $columnInfo = $this->getColumnDetails($tableName, $columnName);

                $columns[] = [
                    'column_name' => $columnName,
                    'data_type' => TypeMappingService::mapDatabaseTypeToLaravel($columnType),
                    'nullable' => $columnInfo['nullable'] ?? false,
                    'unique' => $columnInfo['unique'] ?? false,
                    'is_fillable' => true, // Default to fillable, user can modify later
                    'default_value' => $columnInfo['default'] ?? '',
                ];
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Error reading table structure: '.$e->getMessage(), 0, $e);
        }

        return $columns;
    }

    /**
     * Get detailed column information
     */
    public function getColumnDetails(string $tableName, string $columnName): array
    {
        try {
            // Use raw database queries to get detailed column information
            $connection = DB::connection();
            $database = $connection->getDatabaseName();

            if ($connection->getDriverName() === 'mysql') {
                $result = DB::select('
                    SELECT
                        COLUMN_NAME,
                        IS_NULLABLE,
                        COLUMN_DEFAULT,
                        COLUMN_KEY
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                ', [$database, $tableName, $columnName]);

                if (! empty($result)) {
                    $column = $result[0];

                    return [
                        'nullable' => $column->IS_NULLABLE === 'YES',
                        'unique' => $column->COLUMN_KEY === 'UNI',
                        'default' => $column->COLUMN_DEFAULT,
                    ];
                }
            }

            // Fallback for other database types or if query fails
            return [
                'nullable' => false,
                'unique' => false,
                'default' => null,
            ];
        } catch (\Exception $e) {
            return [
                'nullable' => false,
                'unique' => false,
                'default' => null,
            ];
        }
    }
}
