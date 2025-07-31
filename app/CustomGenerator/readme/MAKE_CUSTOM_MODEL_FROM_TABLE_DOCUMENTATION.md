# make:custom-model-from-table Command Documentation

## Overview

The `make:custom-model-from-table` command creates custom Eloquent models with enhanced features by reading column definitions from existing database tables automatically.

## Command Signature

```bash
php artisan make:custom-model-from-table {name} [options]
```

## Description

Create a custom model with enhanced features by automatically reading the database schema from an existing table. This command extends the functionality of `make:custom-model` by eliminating the need to manually define columns - it reads them directly from the database.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `name` | Required | The name of the model to create |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--all` | - | Flag | Create all related components |
| `--factory` | - | Flag | Create a model factory |
| `--seed` | - | Flag | Create a database seeder |
| `--controller` | - | Flag | Create a controller |
| `--resource` | - | Flag | Create a resource controller |
| `--api` | - | Flag | Create an API controller |
| `--policy` | - | Flag | Create a policy |
| `--requests` | - | Flag | Create form request classes |
| `--repository` | - | Flag | Create a repository for the model |
| `--json-resource` | - | Flag | Create a JSON resource for the model |
| `--cache` | - | Flag | Create a cache class for the model |
| `--soft-deletes` | - | Flag | Add soft deletes to the model |
| `--force` | -f | Flag | Overwrite existing files |

## How It Works

1. **Table Detection**: Automatically derives table name from model name using Laravel conventions
2. **Schema Reading**: Reads existing database table structure using DatabaseColumnReaderService
3. **Column Analysis**: Extracts column properties including:
   - Column names and data types
   - Nullable constraints
   - Unique constraints
   - Default values
   - Fillable status determination
4. **Model Generation**: Creates enhanced model with automatic fillable, casts, and other attributes

## Interactive Features

When run without specific component options, the command provides interactive prompts:

1. **Component Selection**: Multi-select prompt for additional components to generate
2. **Available Options**: Dynamically shows available options based on installed commands

## Usage Examples

### Basic Model from Table

```bash
php artisan make:custom-model-from-table User
```
*Reads from `users` table and creates User model*

### Model with All Components

```bash
php artisan make:custom-model-from-table Product --all
```
*Creates Product model with factory, seeder, controller, requests, repository, cache, and JSON resource*

### Model with Specific Components

```bash
php artisan make:custom-model-from-table Order --controller --requests --repository --json-resource
```

### Model with API Components

```bash
php artisan make:custom-model-from-table Article --api --requests --json-resource
```

## Table Naming Convention

The command follows Laravel's naming conventions:

| Model Name | Expected Table Name |
|------------|-------------------|
| `User` | `users` |
| `BlogPost` | `blog_posts` |
| `ProductCategory` | `product_categories` |
| `OrderItem` | `order_items` |

## Prerequisites

- **Database Connection**: Active database connection required
- **Existing Table**: Target table must exist in the database
- **Table Structure**: Table should have proper column definitions

## Generated Files

Depending on options selected, the command can generate:

- **Model**: `app/Models/{Name}.php` (with auto-detected fillable, casts, etc.)
- **Factory**: `database/factories/{Name}Factory.php`
- **Seeder**: `database/seeders/{Name}Seeder.php`
- **Controller**: `app/Http/Controllers/{Name}Controller.php`
- **API Controller**: `app/Http/Controllers/Api/{Name}Controller.php`
- **Requests**: `app/Http/Requests/Store{Name}Request.php` and `Update{Name}Request.php`
- **Resource**: `app/Http/Resources/{Name}Resource.php`
- **Repository**: `app/Repositories/{Name}Repository.php`
- **Repository Interface**: `app/Repositories/Contracts/{Name}RepositoryInterface.php`
- **Cache**: `app/Cache/{Name}/{Name}ById.php`
- **Policy**: `app/Policies/{Name}Policy.php`

## Enhanced Model Features

The generated model automatically includes:

- **Smart Fillable**: Based on actual database columns (excludes id, timestamps, etc.)
- **Automatic Casts**: Inferred from database column types
- **Relationship Hints**: Comments for potential relationships based on foreign key patterns
- **Soft Deletes**: If `deleted_at` column exists in table
- **Custom Attributes**: Based on database schema analysis

## Error Handling

The command handles various error scenarios:

- **Missing Table**: Prompts with clear error if table doesn't exist
- **Empty Table**: Warns if no columns found
- **Connection Issues**: Database connection problems are reported
- **Reserved Names**: Prevents using PHP reserved words

## Comparison with make:custom-model

| Feature | make:custom-model | make:custom-model-from-table |
|---------|------------------|----------------------------|
| Column Definition | Manual/Interactive | Automatic from DB |
| Table Requirement | None | Must exist |
| Accuracy | User-dependent | Database-accurate |
| Speed | Interactive prompts | Instant schema reading |
| Use Case | New projects | Existing databases |

## Related Commands

- `make:custom-model` - Create model with manual column definition
- `make:custom-migration` - Create enhanced migrations
- `make:custom-request` - Create form requests with validation
- `make:repository` - Create repository pattern classes

## Best Practices

1. **Ensure Clean Schema**: Make sure your database table has proper column types and constraints
2. **Review Generated Code**: Always review the generated model for accuracy
3. **Test Relationships**: Verify that relationship methods work as expected
4. **Update Fillable**: Adjust fillable array if needed for security
5. **Customize Casts**: Modify casts array for specific data handling needs

## Notes

- The command extends `CustomModelMakeCommand` for consistent behavior
- Database schema is cached during execution to avoid multiple queries
- Interactive prompts are context-aware based on available commands
- The `--all` flag automatically enables all available component generation options
