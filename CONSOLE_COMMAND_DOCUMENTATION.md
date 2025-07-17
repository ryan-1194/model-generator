# Console Command for ModelGeneratorService

## Overview
The `generate:model` console command provides a beautiful, interactive command-line interface to the ModelGeneratorService using Laravel Prompts. It allows you to generate Laravel models and related files directly from the terminal with an enhanced user experience that includes automatic preview generation and confirmation prompts.

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

### New Interactive Experience
- **Automatic Preview**: All commands now show a preview of generated files by default
- **Laravel Prompts**: Enhanced interactive experience with beautiful prompts and better UX
- **Confirmation Required**: You'll be asked to confirm before files are actually generated
- **Cancellable**: You can review the preview and cancel generation if needed

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

### Automatic Preview and Confirmation
```bash
# All commands now automatically show preview and ask for confirmation
php artisan generate:model Product --columns='[{"column_name":"name","data_type":"string","nullable":false,"unique":false,"is_fillable":true,"default_value":""}]'

# The command will:
# 1. Show a beautiful preview of all generated files
# 2. Ask "Do you want to generate these files?" with Laravel Prompts
# 3. Only generate files if you confirm with "Yes"
# 4. Allow you to cancel after reviewing the preview
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

## Enhanced Interactive Column Input with Laravel Prompts

If you don't provide the `--columns` option, the command will use beautiful Laravel Prompts for interactive input:

```bash
php artisan generate:model Product --json-resource

# The command will show beautiful prompts:
# ┌ Would you like to add custom columns? ───────────────────────┐
# │ Yes                                                          │
# └──────────────────────────────────────────────────────────────┘
# 
# ┌ Column name ─────────────────────────────────────────────────┐
# │ name                                                         │
# └──────────────────────────────────────────────────────────────┘
# 
# ┌ Data type ───────────────────────────────────────────────────┐
# │ String (VARCHAR)                                             │
# └──────────────────────────────────────────────────────────────┘
# 
# ┌ Should this column be nullable? ─────────────────────────────┐
# │ No                                                           │
# └──────────────────────────────────────────────────────────────┘
# 
# ┌ Should this column be unique? ───────────────────────────────┐
# │ No                                                           │
# └──────────────────────────────────────────────────────────────┘
# 
# ┌ Should this column be fillable? ─────────────────────────────┐
# │ Yes                                                          │
# └──────────────────────────────────────────────────────────────┘
# 
# ┌ Default value ───────────────────────────────────────────────┐
# │                                                              │
# └──────────────────────────────────────────────────────────────┘
# 
# Added column: name (string)
# 
# ┌ Column name ─────────────────────────────────────────────────┐
# │                                                              │
# └──────────────────────────────────────────────────────────────┘
# (Press Enter without typing to finish adding columns)
```

### Laravel Prompts Features:
- **Beautiful UI**: Clean, boxed prompts with clear labels
- **Descriptive Options**: Data types show both name and SQL type (e.g., "String (VARCHAR)")
- **Helpful Hints**: Clear instructions and placeholders
- **Progress Feedback**: Shows confirmation when columns are added
- **Easy Exit**: Simply press Enter without typing to finish

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

1. **Review Before Confirming**: The command automatically shows previews - take time to review them before confirming
2. **Interactive Column Input**: If you don't provide `--columns`, you'll get beautiful Laravel Prompts for interactive input
3. **JSON Formatting**: Use a JSON formatter to ensure your column definitions are valid when using `--columns`
4. **Incremental Generation**: You can run the command multiple times with different options to add more files
5. **Safe by Default**: You can always cancel after seeing the preview - no files are generated until you confirm
6. **Backup**: Consider backing up your project before running file generation commands

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
