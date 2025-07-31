# make:custom-model Command Documentation

## Overview

The `make:custom-model` command creates custom Eloquent models with enhanced features and the ability to generate multiple related components simultaneously.

## Command Signature

```bash
php artisan make:custom-model {name} [options]
```

## Description

Create a custom model with enhanced features including automatic fillable/hidden attributes, casts, and the ability to generate related components like migrations, controllers, requests, repositories, and more.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `name` | Required | The name of the model to create |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--columns` | - | Optional | JSON string of column definitions |
| `--soft-deletes` | - | Flag | Add soft deletes to the model |
| `--no-timestamps` | - | Flag | Disable timestamps on the model |
| `--json-resource` | - | Flag | Create a JSON resource for the model |
| `--repository` | - | Flag | Create a repository for the model |
| `--cache` | - | Flag | Create a cache class for the model |
| `--all` | - | Flag | Create all related components |
| `--factory` | - | Flag | Create a model factory |
| `--seed` | - | Flag | Create a database seeder |
| `--migration` | - | Flag | Create a migration file |
| `--controller` | - | Flag | Create a controller |
| `--resource` | - | Flag | Create a resource controller |
| `--api` | - | Flag | Create an API controller |
| `--policy` | - | Flag | Create a policy |
| `--requests` | - | Flag | Create form request classes |
| `--force` | -f | Flag | Overwrite existing files |

## Interactive Features

When run without options, the command provides interactive prompts using Laravel Prompts:

1. **Column Definition**: Option to add custom columns interactively
2. **Component Selection**: Multi-select prompt for additional components
3. **Column Properties**: For each column, prompts for:
   - Data type (string, integer, boolean, etc.)
   - Nullable status
   - Unique constraint
   - Fillable status
   - Default value

## Usage Examples

### Basic Model Creation

```bash
php artisan make:custom-model User
```

### Model with All Components

```bash
php artisan make:custom-model Product --all
```

### Model with Specific Components

```bash
php artisan make:custom-model Order --migration --controller --requests --repository
```

### Model with Column Definitions

```bash
php artisan make:custom-model Article --columns='[
  {
    "column_name": "title",
    "data_type": "string",
    "nullable": false,
    "unique": false,
    "is_fillable": true,
    "default_value": ""
  },
  {
    "column_name": "content",
    "data_type": "text",
    "nullable": true,
    "unique": false,
    "is_fillable": true,
    "default_value": ""
  }
]'
```

### Model with Soft Deletes

```bash
php artisan make:custom-model Post --soft-deletes --migration
```

## Column Definition JSON Format

When using the `--columns` option, provide a JSON array with the following structure:

```json
[
  {
    "column_name": "field_name",
    "data_type": "string",
    "nullable": true,
    "unique": false,
    "is_fillable": true,
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
- `decimal` - Decimal field
- `float` - Float field
- `json` - JSON field

## Generated Files

Depending on options selected, the command can generate:

- **Model**: `app/Models/{Name}.php` (enhanced with fillable, casts, etc.)
- **Migration**: `database/migrations/{timestamp}_create_{table}_table.php`
- **Factory**: `database/factories/{Name}Factory.php`
- **Seeder**: `database/seeders/{Name}Seeder.php`
- **Controller**: `app/Http/Controllers/{Name}Controller.php`
- **Requests**: `app/Http/Requests/Store{Name}Request.php` and `Update{Name}Request.php`
- **Resource**: `app/Http/Resources/{Name}Resource.php`
- **Repository**: `app/Repositories/{Name}Repository.php`
- **Repository Interface**: `app/Repositories/Contracts/{Name}RepositoryInterface.php`
- **Cache**: `app/Cache/{Name}/{Name}ById.php`
- **Policy**: `app/Policies/{Name}Policy.php`

## Enhanced Model Features

The generated model includes:

- **Automatic Fillable**: Based on column definitions
- **Automatic Hidden**: For sensitive fields
- **Automatic Casts**: Based on data types
- **Soft Deletes**: If specified
- **Timestamps**: Configurable
- **Enhanced Stub**: Uses custom model template

## Related Commands

- `make:custom-model-from-table` - Create model from existing database table
- `make:custom-migration` - Create enhanced migrations
- `make:custom-request` - Create form requests with validation
- `make:repository` - Create repository pattern classes

## Notes

- The command uses enhanced stub files located in `app/CustomGenerator/stubs/`
- Interactive prompts are skipped if options are provided via command line
- The `--all` flag enables all available component generation options
- Repository and cache generation require their respective commands to be available
