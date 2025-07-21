# CustomModelMakeCommand Documentation

## Overview
The `make:custom-model` command is an enhanced version of Laravel's standard `make:model` command that provides advanced features for creating models with enhanced functionality. It extends Laravel's `ModelMakeCommand` and includes interactive prompts, custom column definitions, and automatic generation of related files with enhanced stubs.

## Basic Usage

```bash
php artisan make:custom-model ModelName [options]
```

## Arguments

- `name` - The name of the model (required, must be in StudlyCase format, e.g., BlogPost)

## Options

### Standard Laravel Options (Inherited)
- `--all` - Generate all related files (factory, seeder, migration, controller, policy, resource, requests, repository, json-resource)
- `--controller` - Generate a controller for the model
- `--factory` - Generate a factory for the model
- `--force` - Create the class even if the model already exists
- `--migration` - Generate a migration file for the model
- `--policy` - Generate a policy for the model
- `--resource` - Generate a resource controller for the model
- `--api` - Generate an API controller for the model
- `--requests` - Generate form request classes for the model
- `--seed` - Generate a seeder for the model

### Enhanced Custom Options
- `--columns=JSON` - JSON string of column definitions for custom model generation
- `--soft-deletes` - Add soft deletes functionality to the model
- `--no-timestamps` - Disable timestamps on the model
- `--repository` - Create a repository for the model
- `--json-resource` - Create a JSON resource for the model

## Interactive Features

### Automatic Component Selection
When running the command without specific options, you'll be prompted with an interactive multiselect menu to choose which components to generate:

```
┌ Would you like any of the following? ────────────────────────────────────────┐
│   ◻ Database Seeder                                                          │
│   ◻ Factory                                                                  │
│   ◻ Form Requests                                                            │
│   ◻ Migration                                                                │
│   ◻ Policy                                                                   │
│   ◻ Resource Controller                                                      │
│   ◻ Repository                                                               │
│   ◻ JSON Resource                                                            │
└───────────────────────────────────────────────────────────────────────────────┘
  Use space to select options and enter to confirm
```

Available options:
- **Database Seeder** - Creates a database seeder class
- **Factory** - Creates a model factory for testing
- **Form Requests** - Creates form request classes for validation
- **Migration** - Creates a database migration file
- **Policy** - Creates an authorization policy class
- **Resource Controller** - Creates a resource controller
- **Repository** - Creates a repository pattern implementation
- **JSON Resource** - Creates an API resource class

### Interactive Column Definition
The command provides an interactive column definition feature using Laravel Prompts. Here's the complete interactive flow:

#### Step 1: Column Addition Prompt
```
┌ Would you like to add custom columns? ───────────────────────────────────────┐
│ ● No / ○ Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘
```

#### Step 2: Column Configuration Loop
If you choose "Yes", you'll enter an interactive loop to define each column:

**Column Name Input:**
```
┌ Column name ──────────────────────────────────────────────────────────────────┐
│ Enter column name or leave empty to finish                                   │
│                                                                               │
│ Press Enter without typing to finish adding columns                          │
└───────────────────────────────────────────────────────────────────────────────┘
```

**Data Type Selection:**
```
┌ Data type ────────────────────────────────────────────────────────────────────┐
│ ● string (VARCHAR)                                                           │
│   text (TEXT)                                                                │
│   integer (INT)                                                              │
│   bigInteger (BIGINT)                                                        │
│   boolean (TINYINT)                                                          │
│   date (DATE)                                                                │
│   datetime (DATETIME)                                                        │
│   timestamp (TIMESTAMP)                                                      │
│   decimal (DECIMAL)                                                          │
│   float (FLOAT)                                                              │
│   json (JSON)                                                                │
└───────────────────────────────────────────────────────────────────────────────┘
```

**Nullable Configuration:**
```
┌ Should this column be nullable? ──────────────────────────────────────────────┐
│ ● No / ○ Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘
```

**Unique Constraint Configuration:**
```
┌ Should this column be unique? ────────────────────────────────────────────────┐
│ ● No / ○ Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘
```

**Fillable Configuration:**
```
┌ Should this column be fillable? ──────────────────────────────────────────────┐
│ ○ No / ● Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘
```

**Default Value Input:**
```
┌ Default value ────────────────────────────────────────────────────────────────┐
│ Leave empty for no default value                                             │
│                                                                               │
└───────────────────────────────────────────────────────────────────────────────┘
```

**Confirmation Message:**
After each column is added, you'll see:
```
ℹ Added column: column_name (data_type)
```

#### Available Data Types
The interactive data type selection includes:
- **string** - VARCHAR column for short text (default)
- **text** - TEXT column for longer content
- **integer** - INT column for whole numbers
- **bigInteger** - BIGINT column for large numbers
- **boolean** - TINYINT column for true/false values
- **date** - DATE column for dates only
- **datetime** - DATETIME column for date and time
- **timestamp** - TIMESTAMP column with automatic updates
- **decimal** - DECIMAL column for precise decimal numbers
- **float** - FLOAT column for floating-point numbers
- **json** - JSON column for structured data

## Column Definition JSON Format

When using the `--columns` option, provide a JSON string with the following structure:

```json
[
  {
    "column_name": "name",
    "data_type": "string",
    "nullable": false,
    "unique": false,
    "is_fillable": true,
    "default_value": ""
  },
  {
    "column_name": "email",
    "data_type": "string",
    "nullable": false,
    "unique": true,
    "is_fillable": true,
    "default_value": ""
  },
  {
    "column_name": "age",
    "data_type": "integer",
    "nullable": true,
    "unique": false,
    "is_fillable": true,
    "default_value": "0"
  }
]
```

### Column Properties
- `column_name` (string): The database column name
- `data_type` (string): The column data type (string, integer, text, decimal, etc.)
- `nullable` (boolean): Whether the column accepts null values
- `unique` (boolean): Whether the column has a unique constraint
- `is_fillable` (boolean): Whether the column is mass assignable
- `default_value` (string): Default value for the column

## Examples

### Basic Model Generation
```bash
# Generate a basic model with interactive prompts
php artisan make:custom-model User
```

### Model with All Components
```bash
# Generate model with all related files
php artisan make:custom-model Product --all
```

### Model with Specific Components
```bash
# Generate model with specific components
php artisan make:custom-model Order --migration --factory --repository --json-resource
```

### Model with Soft Deletes
```bash
# Generate model with soft delete functionality
php artisan make:custom-model Post --soft-deletes --migration
```

### Model without Timestamps
```bash
# Generate model without timestamp columns
php artisan make:custom-model Setting --no-timestamps --migration
```

### Model with Custom Columns (JSON)
```bash
# Generate model with predefined columns
php artisan make:custom-model Product --columns='[
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
]' --migration --factory
```

### Model with Repository Pattern
```bash
# Generate model with repository and interface
php artisan make:custom-model Customer --repository --migration --factory
```

## Enhanced Features

### Custom Stubs
The command uses enhanced stub files located in:
- `app/CustomModelGenerator/stubs/model.enhanced.stub` - Enhanced model template
- `app/CustomModelGenerator/stubs/migration.enhanced.stub` - Enhanced migration template
- `app/CustomModelGenerator/stubs/request.enhanced.stub` - Enhanced form request template
- `app/CustomModelGenerator/stubs/resource.enhanced.stub` - Enhanced JSON resource template

### Automatic Code Generation
The command automatically generates:
- **Fillable Arrays**: Based on column definitions
- **Casts Arrays**: Automatic type casting based on data types
- **Migration Schema**: Complete migration with column definitions
- **Form Requests**: With validation rules based on column properties
- **JSON Resources**: With proper attribute mapping

### Type Mapping
The command integrates with `TypeMappingService` to provide:
- Database column type mapping
- PHP type casting
- Validation rule generation
- Default value handling

## Interactive Workflow Example

Here's a complete example of the interactive workflow when running:

```bash
php artisan make:custom-model BlogPost
```

### Step-by-Step Interactive Flow

**1. Component Selection:**
```
┌ Would you like any of the following? ────────────────────────────────────────┐
│ ◼ Database Seeder                                                            │
│ ◼ Factory                                                                    │
│ ◻ Form Requests                                                              │
│ ◼ Migration                                                                  │
│ ◻ Policy                                                                     │
│ ◻ Resource Controller                                                        │
│ ◻ Repository                                                                 │
│ ◻ JSON Resource                                                              │
└───────────────────────────────────────────────────────────────────────────────┘
  Use space to select options and enter to confirm
```

**2. Column Definition Prompt:**
```
┌ Would you like to add custom columns? ───────────────────────────────────────┐
│ ○ No / ● Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘
```

**3. First Column - Title:**
```
┌ Column name ──────────────────────────────────────────────────────────────────┐
│ title                                                                         │
│ Press Enter without typing to finish adding columns                          │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Data type ────────────────────────────────────────────────────────────────────┐
│ ● string (VARCHAR)                                                           │
│   text (TEXT)                                                                │
│   integer (INT)                                                              │
│   ...                                                                        │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be nullable? ──────────────────────────────────────────────┐
│ ● No / ○ Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be unique? ────────────────────────────────────────────────┐
│ ● No / ○ Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be fillable? ──────────────────────────────────────────────┐
│ ○ No / ● Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Default value ────────────────────────────────────────────────────────────────┐
│                                                                               │
│ Leave empty for no default value                                             │
└───────────────────────────────────────────────────────────────────────────────┘

ℹ Added column: title (string)
```

**4. Second Column - Content:**
```
┌ Column name ──────────────────────────────────────────────────────────────────┐
│ content                                                                       │
│ Press Enter without typing to finish adding columns                          │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Data type ────────────────────────────────────────────────────────────────────┐
│   string (VARCHAR)                                                           │
│ ● text (TEXT)                                                                │
│   integer (INT)                                                              │
│   ...                                                                        │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be nullable? ──────────────────────────────────────────────┐
│ ○ No / ● Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be unique? ────────────────────────────────────────────────┐
│ ● No / ○ Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be fillable? ──────────────────────────────────────────────┐
│ ○ No / ● Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Default value ────────────────────────────────────────────────────────────────┐
│                                                                               │
│ Leave empty for no default value                                             │
└───────────────────────────────────────────────────────────────────────────────┘

ℹ Added column: content (text)
```

**5. Third Column - Published At:**
```
┌ Column name ──────────────────────────────────────────────────────────────────┐
│ published_at                                                                  │
│ Press Enter without typing to finish adding columns                          │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Data type ────────────────────────────────────────────────────────────────────┐
│   string (VARCHAR)                                                           │
│   text (TEXT)                                                                │
│   integer (INT)                                                              │
│   bigInteger (BIGINT)                                                        │
│   boolean (TINYINT)                                                          │
│   date (DATE)                                                                │
│ ● datetime (DATETIME)                                                        │
│   timestamp (TIMESTAMP)                                                      │
│   decimal (DECIMAL)                                                          │
│   float (FLOAT)                                                              │
│   json (JSON)                                                                │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be nullable? ──────────────────────────────────────────────┐
│ ○ No / ● Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be unique? ────────────────────────────────────────────────┐
│ ● No / ○ Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Should this column be fillable? ──────────────────────────────────────────────┐
│ ○ No / ● Yes                                                                 │
└───────────────────────────────────────────────────────────────────────────────┘

┌ Default value ────────────────────────────────────────────────────────────────┐
│                                                                               │
│ Leave empty for no default value                                             │
└───────────────────────────────────────────────────────────────────────────────┘

ℹ Added column: published_at (datetime)
```

**6. Finish Adding Columns:**
```
┌ Column name ──────────────────────────────────────────────────────────────────┐
│                                                                               │
│ Press Enter without typing to finish adding columns                          │
└───────────────────────────────────────────────────────────────────────────────┘
```

**7. File Generation:**
The command then generates the selected files with the defined columns:
- `app/Models/BlogPost.php` (with fillable array and casts)
- `database/migrations/xxxx_xx_xx_xxxxxx_create_blog_posts_table.php`
- `database/factories/BlogPostFactory.php`
- `database/seeders/BlogPostSeeder.php`

### Summary
This interactive workflow allows you to:
1. **Select components** using a visual multiselect interface
2. **Define custom columns** with detailed configuration for each
3. **Generate enhanced files** with all your specifications automatically applied

The interactive approach eliminates the need to remember complex JSON syntax and provides a user-friendly way to configure your model generation.

## Best Practices

### Column Naming
- Use snake_case for column names (e.g., `created_at`, `user_id`)
- Be descriptive but concise
- Follow Laravel naming conventions

### Data Types
- Use appropriate data types for your data
- Consider using `decimal` for monetary values
- Use `text` for long content, `string` for shorter text

### Fillable Properties
- Only mark columns as fillable if they should be mass assignable
- Exclude sensitive fields like passwords from fillable arrays
- Consider security implications of mass assignment

### Default Values
- Provide sensible defaults where appropriate
- Use empty strings for required text fields
- Use `0` for numeric fields that shouldn't be null

## Error Handling

The command includes comprehensive error handling:
- **Reserved Names**: Prevents using PHP reserved words as model names
- **Existing Files**: Checks for existing files (use `--force` to override)
- **Missing Stubs**: Validates that required stub files exist
- **Invalid JSON**: Validates JSON format for column definitions

## Integration with Laravel Features

### Eloquent Features
- Automatic relationship detection
- Soft delete integration
- Timestamp management
- Mass assignment protection

### Laravel Ecosystem
- Compatible with Laravel factories
- Works with Laravel seeders
- Integrates with form requests
- Supports API resources

## Troubleshooting

### Common Issues
1. **Stub files not found**: Ensure enhanced stub files exist in the correct directory
2. **Invalid JSON format**: Validate your JSON syntax for column definitions
3. **Permission errors**: Check file system permissions for the target directories

### Debug Tips
- Use `--force` to overwrite existing files during development
- Check the generated files to ensure they match your expectations
- Validate your column JSON format before running the command

## Related Commands

- `make:model` - Standard Laravel model generation command
- `make:migration` - Standard Laravel migration generation
- `make:factory` - Standard Laravel factory generation

This enhanced command provides a powerful and flexible way to generate Laravel models with advanced features while maintaining compatibility with Laravel's standard conventions.
