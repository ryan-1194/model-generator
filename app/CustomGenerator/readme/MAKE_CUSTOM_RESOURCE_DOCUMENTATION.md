# make:custom-resource Command Documentation

## Overview

The `make:custom-resource` command creates JSON resource classes with enhanced field generation that automatically includes resource fields based on database column definitions or JSON input.

## Command Signature

```bash
php artisan make:custom-resource {name} [options]
```

## Description

Create a new JSON resource class with enhanced field generation that automatically populates the `toArray()` method with appropriate resource fields based on database table structure or provided column definitions. This eliminates the need to manually define resource fields.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `name` | Required | The name of the resource class to create |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--columns` | - | Optional | JSON string of column definitions |
| `--model` | - | Optional | Model name to read columns from database table |
| `--soft-deletes` | - | Flag | Include soft deletes field in the resource |
| `--no-timestamps` | - | Flag | Exclude timestamps from the resource |
| `--force` | -f | Flag | Create the class even if the resource already exists |

## Interactive Features

When run without the `--model` option, the command provides an interactive prompt:

- **Model Selection**: Prompts for model name to generate resource fields from the corresponding database table

## How It Works

1. **Column Source**: Reads columns from JSON input or database table via model name
2. **Field Generation**: Creates appropriate resource fields based on column properties
3. **Automatic Fields**: Includes standard fields like `id`, timestamps, and soft deletes
4. **Resource Creation**: Generates JSON resource class with auto-populated `toArray()` method

## Usage Examples

### Basic Resource with Interactive Prompt

```bash
php artisan make:custom-resource UserResource
```
*Prompts for model name and generates resource fields from database*

### Resource from Model

```bash
php artisan make:custom-resource ProductResource --model=Product
```
*Reads from `products` table and generates appropriate resource fields*

### Resource with Column Definitions

```bash
php artisan make:custom-resource ArticleResource --columns='[
  {
    "column_name": "title",
    "data_type": "string",
    "nullable": false,
    "is_fillable": true
  },
  {
    "column_name": "content",
    "data_type": "text",
    "nullable": false,
    "is_fillable": true
  },
  {
    "column_name": "published_at",
    "data_type": "datetime",
    "nullable": true,
    "is_fillable": true
  }
]'
```

### Resource with Soft Deletes

```bash
php artisan make:custom-resource PostResource --model=Post --soft-deletes
```

### Resource without Timestamps

```bash
php artisan make:custom-resource SettingResource --model=Setting --no-timestamps
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

## Generated Resource Structure

The generated JSON resource class includes:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## Automatic Field Generation

The command automatically includes various types of fields:

### Standard Fields

- **ID Field**: Always includes `'id' => $this->id` as the first field
- **Custom Columns**: All columns from database or JSON definition
- **Timestamps**: `created_at` and `updated_at` (unless `--no-timestamps`)
- **Soft Deletes**: `deleted_at` field (if `--soft-deletes` flag used)

### Field Generation Examples

#### Example 1: User Resource

For a `users` table with columns:
- `name` (string)
- `email` (string)
- `email_verified_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)

Generated resource:
```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'email' => $this->email,
    'email_verified_at' => $this->email_verified_at,
    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
];
```

#### Example 2: Product Resource with Soft Deletes

For a `products` table with soft deletes:

```bash
php artisan make:custom-resource ProductResource --model=Product --soft-deletes
```

Generated resource:
```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'price' => $this->price,
    'description' => $this->description,
    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
    'deleted_at' => $this->deleted_at,
];
```

#### Example 3: Settings Resource without Timestamps

For a settings table without timestamps:

```bash
php artisan make:custom-resource SettingResource --model=Setting --no-timestamps
```

Generated resource:
```php
return [
    'id' => $this->id,
    'key' => $this->key,
    'value' => $this->value,
    'type' => $this->type,
];
```

## Field Ordering

The generated fields follow a logical order:

1. **ID field** - Always first
2. **Custom columns** - In the order they appear in the database/JSON
3. **Timestamps** - `created_at`, `updated_at` (if enabled)
4. **Soft deletes** - `deleted_at` (if enabled)

## Error Handling

The command handles various error scenarios:

- **Invalid JSON**: Clear error message for malformed JSON in --columns
- **Missing Table**: Error if specified model's table doesn't exist
- **No Columns**: Creates basic resource structure if no columns found
- **Database Connection**: Graceful handling of database connection issues

## Integration with Other Commands

The resource command integrates with:

- `make:custom-model` - Can be called automatically with --json-resource flag
- `make:custom-model-from-table` - Uses same column reading logic
- Standard Laravel resources - Compatible with all Laravel resource features

## File Location

Generated JSON resource classes are placed in:
```
app/Http/Resources/{ResourceName}.php
```

## Best Practices

1. **Review Generated Fields**: Always review and customize generated resource fields
2. **Add Relationships**: Include related model data using resource relationships
3. **Conditional Fields**: Use `$this->when()` for conditional field inclusion
4. **Nested Resources**: Create nested resources for complex data structures
5. **Performance**: Consider field selection for large datasets

## Customization Examples

### Adding Conditional Fields

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'email_verified_at' => $this->when($this->email_verified_at, $this->email_verified_at),
        'is_admin' => $this->when($request->user()->isAdmin(), $this->is_admin),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

### Adding Relationships

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'price' => $this->price,
        'category' => new CategoryResource($this->whenLoaded('category')),
        'tags' => TagResource::collection($this->whenLoaded('tags')),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

### Adding Computed Fields

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'price' => $this->price,
        'formatted_price' => '$' . number_format($this->price, 2),
        'is_expensive' => $this->price > 100,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

## Advanced Usage

### Resource Collections

The generated resources work seamlessly with Laravel's resource collections:

```php
// Controller usage
return ProductResource::collection(Product::all());

// Paginated resources
return ProductResource::collection(Product::paginate(15));
```

### API Response Wrapping

```php
// Automatic wrapping
return new ProductResource($product);

// Custom wrapping
return (new ProductResource($product))->additional([
    'meta' => [
        'version' => '1.0',
        'generated_at' => now(),
    ],
]);
```

## Related Commands

- `make:custom-model` - Create enhanced models (can include JSON resources)
- `make:custom-model-from-table` - Create models from existing tables
- `make:resource` - Standard Laravel resource command
- `make:custom-request` - Create form requests with similar column reading

## Notes

- Uses enhanced stub file located in `app/CustomGenerator/stubs/resource.enhanced.stub`
- Automatically includes `id` field as the first field in all resources
- Timestamps are included by default unless `--no-timestamps` is specified
- Soft deletes field is only included when `--soft-deletes` flag is used
- Compatible with all Laravel JSON resource features including relationships, conditional fields, and collections
- The generated resource follows Laravel's resource conventions and best practices
