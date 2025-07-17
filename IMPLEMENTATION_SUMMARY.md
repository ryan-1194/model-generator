# Laravel Model Generator with Filament Admin Panel

## Overview
This project implements a comprehensive Laravel model generator with a Filament admin panel interface. Users can define models through a web interface and automatically generate Laravel models, migrations, factories, policies, and resource controllers.

## Implemented Features

### Phase 1: Project Setup ✅
- Laravel project with Filament Admin Panel v3.3
- Admin user created (admin@test.com)
- Authentication handled by Filament
- File permissions configured for code generation

### Phase 2: Table/Model Definition UI ✅
- **ModelDefinition Model**: Stores model configurations
  - `model_name` (required, StudlyCase)
  - `table_name` (optional, auto-generated from model name)
  - `generate_migration` (boolean toggle)
  - `has_timestamps` (boolean toggle)
  - `has_soft_deletes` (boolean toggle)

- **ModelColumn Model**: Stores column definitions
  - `column_name` (required)
  - `data_type` (select: string, text, integer, boolean, date, etc.)
  - `nullable`, `unique` (boolean toggles)
  - `default_value` (optional)
  - `is_fillable` (boolean toggle)
  - `cast_type` (optional select)
  - `order` (for sorting)

- **Filament Resource**: Complete admin interface
  - Form with model configuration fields
  - Repeater for column definitions
  - Table view with all model definitions
  - Search and filter capabilities

### Phase 3: Code Generator Backend ✅
- **ModelGeneratorService**: Core generation logic
  - Uses Laravel Artisan commands for base file generation
  - Modifies generated stubs with custom content
  - Generates fillable arrays dynamically
  - Adds casts based on column definitions
  - Handles soft deletes and timestamps configuration
  - Creates migration files with proper column definitions

- **Preview Functionality**: 
  - Real-time code preview before generation
  - Shows both model and migration code
  - Syntax-highlighted display in modal

- **File Generation**:
  - Model files in `app/Models/`
  - Migration files in `database/migrations/`
  - Factory files in `database/factories/`
  - Policy files in `app/Policies/`
  - Resource controllers in `app/Http/Controllers/`

## How to Use

### 1. Access the Admin Panel
- Navigate to `/admin` in your browser
- Login with: admin@test.com / [password set during setup]

### 2. Create a Model Definition
1. Go to "Model Definitions" in the admin panel
2. Click "Create"
3. Fill in the model configuration:
   - **Model Name**: Use StudlyCase (e.g., "BlogPost")
   - **Table Name**: Leave empty for auto-generation or specify custom name
   - **Generate Migration**: Toggle to create migration file
   - **Has Timestamps**: Toggle to include created_at/updated_at
   - **Has Soft Deletes**: Toggle to include soft delete functionality

### 3. Define Columns
1. In the "Columns" section, click "Add Column"
2. For each column, specify:
   - **Column Name**: Database column name
   - **Data Type**: Select from available types
   - **Nullable**: Whether column can be null
   - **Unique**: Whether column should be unique
   - **Default Value**: Optional default value
   - **Is Fillable**: Include in model's fillable array
   - **Cast Type**: How to cast the attribute in the model

### 4. Preview and Generate
1. **Preview**: Click the eye icon to see generated code
2. **Generate**: Click the code bracket icon to create actual files

## Generated Files Structure

### Model File Example
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'content',
        'is_published'
    ];

    protected $casts = [
        'title' => 'string',
        'is_published' => 'boolean'
    ];
}
```

### Migration File Example
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_published')->default('false');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
```

## Technical Implementation

### Key Components
1. **Models**:
   - `ModelDefinition`: Main model configuration
   - `ModelColumn`: Column definitions with relationships

2. **Service**:
   - `ModelGeneratorService`: Handles all code generation logic

3. **Filament Resource**:
   - `ModelDefinitionResource`: Admin interface with forms and tables

4. **Views**:
   - `code-preview.blade.php`: Displays generated code previews

### Database Schema
- `model_definitions` table: Stores model configurations
- `model_columns` table: Stores column definitions with foreign key to model_definitions

### Features Implemented
- ✅ Dynamic form with live updates
- ✅ Auto-generation of table names from model names
- ✅ Comprehensive column type support
- ✅ Real-time code preview
- ✅ File generation with proper Laravel conventions
- ✅ Error handling and user notifications
- ✅ Relationship management between models and columns

## Future Enhancements (Phase 5)
- Generate Filament Resources automatically
- Add support for model relationships (hasMany, belongsTo, etc.)
- Generate test files
- Add foreign key and index support
- Implement rollback/undo functionality
- Add JSON export/import of model definitions

## Usage Notes
- Always preview code before generating to ensure correctness
- Generated files will overwrite existing files with the same name
- Migration files are timestamped and can be run with `php artisan migrate`
- All generated files follow Laravel conventions and best practices
