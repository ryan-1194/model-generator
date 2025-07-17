# Console Command for ModelGeneratorService

## Overview
The `generate:model` console command provides a command-line interface to the ModelGeneratorService, allowing you to generate Laravel models and related files directly from the terminal.

## Basic Usage

```bash
php artisan generate:model ModelName [options]
```

## Arguments

- `name` - The name of the model (required, must be in StudlyCase format, e.g., BlogPost)

## Options

### File Generation Options
- `--migration` - Generate migration file (default: enabled)
- `--no-migration` - Skip migration generation
- `--factory` - Generate factory file (default: enabled)
- `--no-factory` - Skip factory generation
- `--policy` - Generate policy file (default: enabled)
- `--no-policy` - Skip policy generation
- `--resource-controller` - Generate resource controller
- `--json-resource` - Generate JSON resource
- `--api-controller` - Generate API controller
- `--form-request` - Generate form request
- `--repository` - Generate repository and interface

### Model Configuration Options
- `--table=TABLE_NAME` - Specify custom table name (default: snake_case plural of model name)
- `--timestamps` - Include timestamps (default: enabled)
- `--no-timestamps` - Skip timestamps
- `--soft-deletes` - Include soft delete functionality

### Column Definition Options
- `--columns=JSON` - Define columns using JSON string
- `--preview` - Show preview only, do not generate files

## Examples

### Basic Model Generation
```bash
# Generate a basic model with default options (migration, factory, policy)
php artisan generate:model User
```

### Model with Additional Files
```bash
# Generate model with JSON resource and form request
php artisan generate:model Product --json-resource --form-request
```

### Model with Custom Table Name
```bash
# Generate model with custom table name
php artisan generate:model BlogPost --table=blog_articles
```

### Model with Soft Deletes
```bash
# Generate model with soft delete functionality
php artisan generate:model Post --soft-deletes
```

### Model with Custom Columns (JSON)
```bash
# Generate model with custom columns defined in JSON
php artisan generate:model Product --json-resource --form-request --columns='[
  {
    "column_name": "name",
    "data_type": "string",
    "nullable": false,
    "unique": false,
    "is_fillable": true,
    "default_value": ""
  },
  {
    "column_name": "price",
    "data_type": "decimal",
    "nullable": false,
    "unique": false,
    "is_fillable": true,
    "default_value": "0.00"
  },
  {
    "column_name": "description",
    "data_type": "text",
    "nullable": true,
    "unique": false,
    "is_fillable": true,
    "default_value": ""
  }
]'
```

### Preview Mode
```bash
# Preview generated code without creating files
php artisan generate:model Product --preview --columns='[{"column_name":"name","data_type":"string","nullable":false,"unique":false,"is_fillable":true,"default_value":""}]'
```

### Complete Example with All Options
```bash
# Generate a complete model with all available files
php artisan generate:model BlogPost \
  --json-resource \
  --api-controller \
  --form-request \
  --repository \
  --soft-deletes \
  --columns='[
    {
      "column_name": "title",
      "data_type": "string",
      "nullable": false,
      "unique": false,
      "is_fillable": true,
      "default_value": ""
    },
    {
      "column_name": "slug",
      "data_type": "string",
      "nullable": false,
      "unique": true,
      "is_fillable": true,
      "default_value": ""
    },
    {
      "column_name": "content",
      "data_type": "text",
      "nullable": false,
      "unique": false,
      "is_fillable": true,
      "default_value": ""
    },
    {
      "column_name": "published_at",
      "data_type": "timestamp",
      "nullable": true,
      "unique": false,
      "is_fillable": true,
      "default_value": ""
    },
    {
      "column_name": "is_featured",
      "data_type": "boolean",
      "nullable": false,
      "unique": false,
      "is_fillable": true,
      "default_value": "false"
    }
  ]'
```

## Interactive Column Input

If you don't provide the `--columns` option, the command will ask if you want to add custom columns interactively:

```bash
php artisan generate:model Product --json-resource

# The command will prompt:
# Would you like to add custom columns? (yes/no) [no]:
# > yes
# 
# Column name (or press Enter to finish):
# > name
# 
# Data type:
#   [0] string
#   [1] text
#   [2] integer
#   [3] bigInteger
#   [4] boolean
#   [5] date
#   [6] datetime
#   [7] timestamp
#   [8] decimal
#   [9] float
#   [10] json
# > 0
# 
# Nullable? (yes/no) [no]:
# > no
# 
# Unique? (yes/no) [no]:
# > no
# 
# Fillable? (yes/no) [yes]:
# > yes
# 
# Default value (optional):
# > 
# 
# Column name (or press Enter to finish):
# > (press Enter to finish)
```

## Column Definition Format

When using the `--columns` option, provide a JSON array with the following structure:

```json
[
  {
    "column_name": "string",
    "data_type": "string",
    "nullable": false,
    "unique": false,
    "is_fillable": true,
    "default_value": ""
  }
]
```

**Field Descriptions:**
- `column_name` (string, required): Column name
- `data_type` (string, required): Data type (string, text, integer, etc.)
- `nullable` (boolean, optional): Whether column can be null (default: false)
- `unique` (boolean, optional): Whether column should be unique (default: false)
- `is_fillable` (boolean, optional): Include in fillable array (default: true)
- `default_value` (string, optional): Default value for column

### Supported Data Types
- `string` - VARCHAR column
- `text` - TEXT column
- `integer` - INTEGER column
- `bigInteger` - BIGINT column
- `boolean` - BOOLEAN column
- `date` - DATE column
- `datetime` - DATETIME column
- `timestamp` - TIMESTAMP column
- `decimal` - DECIMAL column
- `float` - FLOAT column
- `json` - JSON column

## Generated Files

The command can generate the following files based on the options provided:

1. **Model** - `app/Models/ModelName.php` (always generated)
2. **Migration** - `database/migrations/YYYY_MM_DD_HHMMSS_create_table_name_table.php`
3. **Factory** - `database/factories/ModelNameFactory.php`
4. **Policy** - `app/Policies/ModelNamePolicy.php`
5. **Resource Controller** - `app/Http/Controllers/ModelNameController.php`
6. **JSON Resource** - `app/Http/Resources/ModelNameResource.php`
7. **API Controller** - `app/Http/Controllers/Api/ModelNameApiController.php`
8. **Form Request** - `app/Http/Requests/ModelNameRequest.php`
9. **Repository** - `app/Repositories/ModelNameRepository.php`
10. **Repository Interface** - `app/Repositories/Contracts/ModelNameRepositoryInterface.php`

## Error Handling

The command includes validation and error handling:

- Model name must be in StudlyCase format
- JSON column definition must be valid JSON
- Required dependencies must be available

## Integration with Filament Admin Panel

This console command uses the same ModelGeneratorService that powers the Filament admin panel interface, ensuring consistency between web and console-based model generation.

## Tips

1. **Use Preview Mode**: Always use `--preview` first to see what will be generated
2. **JSON Formatting**: Use a JSON formatter to ensure your column definitions are valid
3. **Incremental Generation**: You can run the command multiple times with different options to add more files
4. **Backup**: Consider backing up your project before running file generation commands

## Troubleshooting

### Command Not Found
If the command is not available, ensure it's registered in `app/Providers/AppServiceProvider.php`:

```php
$this->commands(GenerateModelCommand::class);
```

### Invalid JSON Error
If you get a JSON parsing error, validate your JSON using an online JSON validator.

### Permission Errors
Ensure your Laravel application has write permissions to the directories where files will be generated.
