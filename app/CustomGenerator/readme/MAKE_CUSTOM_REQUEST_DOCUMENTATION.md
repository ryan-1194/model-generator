# make:custom-request Command Documentation

## Overview

The `make:custom-request` command creates form request classes with enhanced validation rules that are automatically generated based on database column definitions or JSON input.

## Command Signature

```bash
php artisan make:custom-request {name} [options]
```

## Description

Create a new form request class with enhanced validation rules that are automatically generated based on database table structure or provided column definitions. The command intelligently creates validation rules based on column data types, nullable constraints, and unique requirements.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `name` | Required | The name of the form request class to create |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--columns` | - | Optional | JSON string of column definitions |
| `--model` | - | Optional | Model name to read columns from database table |
| `--force` | -f | Flag | Create the class even if the request already exists |

## Interactive Features

When run without the `--model` option, the command provides an interactive prompt:

- **Model Selection**: Prompts for model name to generate validation rules from the corresponding database table

## How It Works

1. **Column Source**: Reads columns from JSON input or database table via model name
2. **Rule Generation**: Creates appropriate validation rules based on column properties:
   - Data type validation (string, integer, boolean, etc.)
   - Required/nullable constraints
   - Unique validation rules
   - Length constraints for string fields
3. **Request Creation**: Generates form request class with auto-generated rules method

## Usage Examples

### Basic Request with Interactive Prompt

```bash
php artisan make:custom-request StoreUserRequest
```
*Prompts for model name and generates validation rules from database*

### Request from Model

```bash
php artisan make:custom-request StoreProductRequest --model=Product
```
*Reads from `products` table and generates appropriate validation rules*

### Request with Column Definitions

```bash
php artisan make:custom-request CreateArticleRequest --columns='[
  {
    "column_name": "title",
    "data_type": "string",
    "nullable": false,
    "unique": true,
    "is_fillable": true
  },
  {
    "column_name": "content",
    "data_type": "text",
    "nullable": false,
    "unique": false,
    "is_fillable": true
  },
  {
    "column_name": "published_at",
    "data_type": "datetime",
    "nullable": true,
    "unique": false,
    "is_fillable": true
  }
]'
```

### Force Overwrite Existing Request

```bash
php artisan make:custom-request UpdateUserRequest --model=User --force
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

## Validation Rule Generation

The command automatically generates validation rules based on column properties:

### Data Type Rules

| Data Type | Generated Rule |
|-----------|----------------|
| `string` | `string\|max:255` |
| `text` | `string` |
| `integer` | `integer` |
| `bigInteger` | `integer` |
| `boolean` | `boolean` |
| `datetime` | `date` |
| `date` | `date` |
| `timestamp` | `date` |
| `decimal` | `numeric` |
| `float` | `numeric` |
| `json` | `array` |

### Constraint Rules

- **Required/Nullable**: `required` or `nullable` based on column nullable property
- **Unique**: `unique:table_name,column_name` for unique columns
- **String Length**: `max:255` for string fields (default Laravel string length)

## Generated Request Structure

The generated form request class includes:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'category_id' => 'required|integer',
            'is_active' => 'required|boolean',
        ];
    }
}
```

## Smart Rule Generation Examples

### Example 1: User Registration Request

For a `users` table with columns:
- `name` (string, not null)
- `email` (string, not null, unique)
- `password` (string, not null)
- `birth_date` (date, nullable)

Generated rules:
```php
return [
    'name' => 'required|string|max:255',
    'email' => 'required|string|max:255|unique:users,email',
    'password' => 'required|string|max:255',
    'birth_date' => 'nullable|date',
];
```

### Example 2: Product Creation Request

For a `products` table with columns:
- `name` (string, not null)
- `slug` (string, not null, unique)
- `price` (decimal, not null)
- `description` (text, nullable)
- `is_featured` (boolean, not null)

Generated rules:
```php
return [
    'name' => 'required|string|max:255',
    'slug' => 'required|string|max:255|unique:products,slug',
    'price' => 'required|numeric',
    'description' => 'nullable|string',
    'is_featured' => 'required|boolean',
];
```

## Model Name Extraction

The command intelligently extracts model names from request class names:

| Request Name | Extracted Model | Table Name |
|--------------|-----------------|------------|
| `StoreUserRequest` | `User` | `users` |
| `UpdateProductRequest` | `Product` | `products` |
| `CreateArticleRequest` | `Article` | `articles` |
| `UserStoreRequest` | `User` | `users` |

## Error Handling

The command handles various error scenarios:

- **Invalid JSON**: Clear error message for malformed JSON in --columns
- **Missing Table**: Error if specified model's table doesn't exist
- **No Columns**: Warning if no fillable columns found
- **Database Connection**: Graceful handling of database connection issues

## Integration with Other Commands

The request command integrates with:

- `make:custom-model` - Can be called automatically with --requests flag
- `make:custom-model-from-table` - Uses same column reading logic
- Standard Laravel validation - Compatible with all Laravel validation features

## File Location

Generated form request classes are placed in:
```
app/Http/Requests/{RequestName}.php
```

## Best Practices

1. **Review Generated Rules**: Always review and customize generated validation rules
2. **Add Custom Rules**: Add business logic validation rules as needed
3. **Authorization Logic**: Update the `authorize()` method based on your requirements
4. **Custom Messages**: Add custom validation messages in `messages()` method
5. **Form Request Features**: Utilize Laravel's form request features like `prepareForValidation()`

## Customization Examples

### Adding Custom Validation Rules

```php
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'age' => 'required|integer|min:18|max:120',
    ];
}
```

### Adding Custom Error Messages

```php
public function messages(): array
{
    return [
        'name.required' => 'The name field is required.',
        'email.unique' => 'This email address is already taken.',
        'password.min' => 'Password must be at least 8 characters.',
    ];
}
```

## Related Commands

- `make:custom-model` - Create enhanced models (can include form requests)
- `make:custom-model-from-table` - Create models from existing tables
- `make:request` - Standard Laravel form request command
- `make:custom-resource` - Create JSON resources with similar column reading

## Notes

- Uses enhanced stub file located in `app/CustomGenerator/stubs/request.enhanced.stub`
- Only generates rules for fillable columns (where `is_fillable: true`)
- Unique validation includes table name for proper constraint checking
- The `authorize()` method defaults to `true` - customize based on your needs
- Compatible with all Laravel form request features and validation rules
