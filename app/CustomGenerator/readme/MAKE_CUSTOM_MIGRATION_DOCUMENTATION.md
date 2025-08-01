# make:custom-migration Command Documentation

## Overview

The `make:custom-migration` command creates custom migration files with enhanced features, including automatic column definition generation from JSON or existing database tables.

## Command Signature

```bash
php artisan make:custom-migration {model} [options]
```

## Description

Create a custom migration file with enhanced features that can automatically generate column definitions based on JSON input or by reading from existing database tables. The migration follows Laravel conventions and includes timestamps and soft deletes options.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `model` | Required | The name of the model for which to create the migration |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--columns` | - | Optional | JSON string of column definitions |
| `--soft-deletes` | - | Flag | Add soft deletes to the migration |
| `--no-timestamps` | - | Flag | Disable timestamps on the migration |
| `--force` | -f | Flag | Create the migration even if it already exists |

## How It Works

1. **Model to Table**: Converts model name to table name using Laravel conventions
2. **Timestamp Generation**: Creates timestamped migration filename
3. **Column Processing**: Processes columns from JSON or existing database table
4. **Migration Generation**: Creates migration with proper up/down methods
5. **Enhanced Features**: Adds timestamps and soft deletes as specified

## Usage Examples

### Basic Migration

```bash
php artisan make:custom-migration User
```
*Creates migration for `users` table with basic structure*

### Migration with Column Definitions

```bash
php artisan make:custom-migration Product --columns='[
  {
    "column_name": "name",
    "data_type": "string",
    "nullable": false,
    "unique": false,
    "default_value": ""
  },
  {
    "column_name": "price",
    "data_type": "decimal",
    "nullable": false,
    "unique": false,
    "default_value": "0.00"
  },
  {
    "column_name": "description",
    "data_type": "text",
    "nullable": true,
    "unique": false,
    "default_value": ""
  }
]'
```

### Migration with Soft Deletes

```bash
php artisan make:custom-migration Post --soft-deletes
```

### Migration without Timestamps

```bash
php artisan make:custom-migration Setting --no-timestamps
```

### Migration from Existing Table

```bash
php artisan make:custom-migration User
```
*If `users` table exists, reads column definitions automatically*

## Column Definition JSON Format

When using the `--columns` option, provide a JSON array with the following structure:

```json
[
  {
    "column_name": "field_name",
    "data_type": "string",
    "nullable": true,
    "unique": false,
    "default_value": ""
  }
]
```

### Available Data Types

- `string` - VARCHAR field
- `integer` - Integer field
- `bigInteger` - Big integer field
- `boolean` - Boolean field
- `text` - TEXT field
- `datetime` - DateTime field
- `date` - Date field
- `timestamp` - Timestamp field
- `decimal` - Decimal field (precision can be specified)
- `float` - Float field
- `json` - JSON field

## Generated Migration Structure

The generated migration includes:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            
            // Custom column definitions
            $table->string('name');
            $table->decimal('price', 8, 2)->default(0.00);
            $table->text('description')->nullable();
            
            $table->timestamps(); // Unless --no-timestamps
            $table->softDeletes(); // If --soft-deletes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

## Model to Table Name Conversion

The command follows Laravel's naming conventions:

| Model Name | Generated Table Name | Migration Name |
|------------|---------------------|----------------|
| `User` | `users` | `create_users_table` |
| `BlogPost` | `blog_posts` | `create_blog_posts_table` |
| `ProductCategory` | `product_categories` | `create_product_categories_table` |
| `OrderItem` | `order_items` | `create_order_items_table` |

## File Naming Convention

Generated migration files follow Laravel's timestamped naming:

```
{YYYY_MM_DD_HHMMSS}_create_{table_name}_table.php
```

Example: `2024_01_15_143022_create_products_table.php`

## Automatic Column Detection

When no `--columns` option is provided, the command attempts to:

1. **Check Existing Table**: Look for existing table with the same name
2. **Read Schema**: If table exists, read column definitions automatically
3. **Generate Migration**: Create migration based on existing structure
4. **Fallback**: If no table exists, create basic migration structure

## Column Definition Features

### Data Type Mapping

The command properly maps data types to Laravel migration methods:

- `string` → `$table->string('column')`
- `integer` → `$table->integer('column')`
- `boolean` → `$table->boolean('column')`
- `decimal` → `$table->decimal('column', 8, 2)`
- `json` → `$table->json('column')`

### Constraint Handling

- **Nullable**: `->nullable()` modifier added when `nullable: true`
- **Unique**: `->unique()` modifier added when `unique: true`
- **Default Values**: `->default(value)` added when default_value specified

## Error Handling

The command handles various scenarios:

- **Invalid JSON**: Clear error message for malformed JSON in --columns
- **Existing Migration**: Prevents overwriting unless --force is used
- **Database Errors**: Graceful handling of database connection issues
- **Invalid Data Types**: Validation of supported data types

## Integration with Other Commands

The migration command works seamlessly with:

- `make:custom-model` - Can be called automatically with --migration flag
- `make:custom-model-from-table` - Uses same column reading logic
- Standard Laravel migration commands - Compatible with `migrate`, `migrate:rollback`, etc.

## Best Practices

1. **Review Generated Code**: Always review the generated migration before running
2. **Test Rollback**: Ensure the down() method works correctly
3. **Column Order**: Consider the logical order of columns in your table
4. **Indexes**: Add indexes manually for performance-critical columns
5. **Foreign Keys**: Add foreign key constraints manually as needed

## Related Commands

- `make:custom-model` - Create enhanced models (can include migrations)
- `make:custom-model-from-table` - Create models from existing tables
- `php artisan migrate` - Run the generated migrations
- `php artisan migrate:rollback` - Rollback migrations

## Notes

- Uses enhanced stub file located in `app/CustomGenerator/stubs/migration.enhanced.stub`
- Automatically includes `id()` primary key and timestamps (unless disabled)
- Soft deletes are optional and only added when explicitly requested
- The command creates timestamped migration files to maintain proper order
- Compatible with all Laravel migration features and commands
