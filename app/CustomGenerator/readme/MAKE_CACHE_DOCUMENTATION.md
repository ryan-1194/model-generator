# make:cache Command Documentation

## Overview

The `make:cache` command creates cache classes following the `{Model}By{PrimaryKey}` pattern, providing a structured approach to model-based caching with automatic base class generation.

## Command Signature

```bash
php artisan make:cache {model} [primary_key] [name] [options]
```

## Description

Create a new cache class that follows a specific naming pattern for model-based caching. The command generates cache classes that extend a base cache class and implement caching patterns with TTL support, cache invalidation, and helper methods.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `model` | Required | The model name for the cache class |
| `primary_key` | Optional | The primary key field name (default: Id) |
| `name` | Optional | The name of the cache class (auto-generated if not provided) |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--type` | -t | Optional | The type of the primary key (default: int) |
| `--force` | -f | Flag | Create the class even if the cache already exists |

## How It Works

1. **Model Validation**: Validates that the specified model exists (prompts to create if missing)
2. **Repository Validation**: Ensures the corresponding repository interface exists
3. **Base Files Generation**: Creates base cache files on first run (CacheBase, WithHelpers trait)
4. **Cache Class Creation**: Generates model-specific cache class with proper naming
5. **Directory Structure**: Creates organized cache directory structure

## First Run Setup

On the first execution, the command automatically generates base files:

### CacheBase (`app/Cache/CacheBase.php`)
- Abstract base class for all cache implementations
- Provides common caching methods (fetch, store, invalidate, exists)
- Includes TTL support and cache miss handling
- Implements fetchOrFail method with ModelNotFoundException

### WithHelpers Trait (`app/Cache/Traits/WithHelpers.php`)
- Static helper methods for convenient cache access
- Methods: `get()`, `forget()`, `getOrFail()`, `forgetTags()`
- Enables fluent cache operations

## Usage Examples

### Basic Cache Creation

```bash
php artisan make:cache User
```
*Creates UserById cache class for User model*

### Cache with Custom Primary Key

```bash
php artisan make:cache Product Slug
```
*Creates ProductBySlug cache class*

### Cache with Custom Name

```bash
php artisan make:cache User Id UserCache
```
*Creates UserCache class instead of UserById*

### Cache with Primary Key Type

```bash
php artisan make:cache Product Uuid --type=string
```
*Creates ProductByUuid cache class with string type*

### Force Overwrite Existing Cache

```bash
php artisan make:cache User --force
```

## Generated Directory Structure

```
app/
├── Cache/
│   ├── CacheBase.php
│   ├── Traits/
│   │   └── WithHelpers.php
│   ├── User/
│   │   └── UserById.php
│   └── Product/
│       ├── ProductById.php
│       └── ProductBySlug.php
```

## Generated Cache Class

The generated cache class extends CacheBase and implements model-specific caching:

```php
<?php

namespace App\Cache\User;

use App\Cache\CacheBase;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserById extends CacheBase
{
    protected UserRepositoryInterface $repository;

    public function __construct(int $id, UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
        parent::__construct("users.{$id}", 3600); // 1 hour TTL
    }

    protected function cacheMiss()
    {
        return $this->repository->find($this->id);
    }

    protected function errorModelName(): string
    {
        return 'User';
    }

    protected function errorModelId(): mixed
    {
        return $this->id;
    }
}
```

## Cache Key Patterns

The command generates cache keys following consistent patterns:

| Model | Primary Key | Generated Key Pattern |
|-------|-------------|----------------------|
| `User` | `Id` | `users.{$this->id}` |
| `Product` | `Slug` | `products.{$this->slug}` |
| `Article` | `Uuid` | `articles.{$this->uuid}` |
| `BlogPost` | `Id` | `blog_posts.{$this->id}` |

## CacheBase Methods

All generated cache classes inherit methods from CacheBase:

```php
// Core caching methods
public function fetch();                    // Get cached data or execute cacheMiss()
public function fetchOrFail();             // Get cached data or throw ModelNotFoundException
public function store($data): bool;        // Store data in cache
public function invalidate(): bool;        // Remove data from cache
public function exists(): bool;            // Check if cache key exists

// Key management
public function getKey();                   // Get the cache key

// Abstract methods (implemented by generated classes)
abstract protected function cacheMiss();   // Define what happens on cache miss
abstract protected function errorModelName(): string;
abstract protected function errorModelId(): mixed;
```

## WithHelpers Trait Methods

The trait provides static helper methods for convenient access:

```php
// Static helper methods
UserById::get($id);                        // Equivalent to (new UserById($id))->fetch()
UserById::forget($id);                     // Equivalent to (new UserById($id))->invalidate()
UserById::getOrFail($id);                  // Equivalent to (new UserById($id))->fetchOrFail()
UserById::forgetTags($id);                 // Invalidate cache tags (if implemented)
```

## Usage Examples

### Basic Cache Usage

```php
// Using constructor
$cache = new UserById(1, app(UserRepositoryInterface::class));
$user = $cache->fetch();

// Using static helpers
$user = UserById::get(1);
$user = UserById::getOrFail(1); // Throws ModelNotFoundException if not found

// Cache invalidation
UserById::forget(1);
```

### In Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Cache\User\UserById;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function show(int $id)
    {
        try {
            $user = UserById::getOrFail($id);
            return view('users.show', compact('user'));
        } catch (ModelNotFoundException $e) {
            abort(404, 'User not found');
        }
    }

    public function update(Request $request, int $id)
    {
        // Update user logic here...
        
        // Invalidate cache after update
        UserById::forget($id);
        
        return redirect()->route('users.show', $id);
    }
}
```

### In Services

```php
<?php

namespace App\Services;

use App\Cache\User\UserById;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserService
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getUserById(int $id)
    {
        return UserById::get($id);
    }

    public function updateUser(int $id, array $data)
    {
        $user = $this->userRepository->update($id, $data);
        
        // Invalidate cache after update
        UserById::forget($id);
        
        return $user;
    }
}
```

## Custom Cache Implementations

### Cache with Custom TTL

```php
<?php

namespace App\Cache\Product;

use App\Cache\CacheBase;

class ProductBySlug extends CacheBase
{
    public function __construct(string $slug, ProductRepositoryInterface $repository)
    {
        $this->repository = $repository;
        parent::__construct("products.{$slug}", 7200); // 2 hours TTL
    }

    protected function cacheMiss()
    {
        return $this->repository->findBySlug($this->slug);
    }
}
```

### Cache with Tags

```php
<?php

namespace App\Cache\User;

use App\Cache\CacheBase;
use Illuminate\Support\Facades\Cache;

class UserById extends CacheBase
{
    public function fetch()
    {
        return Cache::tags(['users', "user.{$this->id}"])
            ->remember($this->getKey(), $this->ttl, function () {
                return $this->cacheMiss();
            });
    }

    public function invalidateTags(): bool
    {
        return Cache::tags(['users', "user.{$this->id}"])->flush();
    }
}
```

## Prerequisites and Validation

The command validates prerequisites before creating cache classes:

### Model Validation
- Checks if the specified model class exists
- Prompts to create model if missing
- Uses `make:model` command for model creation

### Repository Validation
- Validates that the corresponding repository interface exists
- Prompts to create repository if missing
- Uses `make:repository` command for repository creation

## Error Handling

The command handles various error scenarios:

- **Missing Model**: Prompts to create model or throws exception
- **Missing Repository**: Prompts to create repository or throws exception
- **Invalid Arguments**: Validates model names and primary key types
- **Directory Creation**: Automatically creates necessary directories
- **Naming Conflicts**: Handles cache class naming conflicts

## Integration with Other Commands

The cache command integrates with:

- `make:custom-model` - Can be called automatically with --cache flag
- `make:custom-model-from-table` - Uses cache pattern for data access
- `make:repository` - Validates repository existence before creating cache
- Model and repository commands - Automatically creates dependencies if missing

## Best Practices

1. **Consistent Naming**: Follow the `{Model}By{PrimaryKey}` pattern
2. **Appropriate TTL**: Set cache TTL based on data volatility
3. **Cache Invalidation**: Always invalidate cache after data updates
4. **Error Handling**: Use `fetchOrFail()` when data must exist
5. **Repository Pattern**: Always use repositories in cache miss methods
6. **Testing**: Mock repositories when testing cache classes

## Testing Cache Classes

```php
<?php

namespace Tests\Unit\Cache;

use Tests\TestCase;
use App\Models\User;
use App\Cache\User\UserById;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Mockery;

class UserByIdTest extends TestCase
{
    public function test_cache_returns_user_on_hit()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($user);

        $mockRepository = Mockery::mock(UserRepositoryInterface::class);
        $cache = new UserById(1, $mockRepository);
        
        $result = $cache->fetch();
        
        $this->assertEquals($user, $result);
    }

    public function test_cache_calls_repository_on_miss()
    {
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        
        $mockRepository = Mockery::mock(UserRepositoryInterface::class);
        $mockRepository->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn($user);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $cache = new UserById(1, $mockRepository);
        $result = $cache->fetch();
        
        $this->assertEquals($user, $result);
    }
}
```

## Related Commands

- `make:custom-model` - Create enhanced models (can include cache classes)
- `make:repository` - Create repository classes (required for cache classes)
- `make:repository-interface` - Create repository interfaces (required for cache classes)

## Notes

- Uses stub file located in `app/CustomGenerator/stubs/cache.stub`
- Automatically creates base files (CacheBase, WithHelpers) on first run
- Validates model and repository existence before creation
- Follows consistent naming patterns for cache keys and classes
- Compatible with Laravel's cache system and all cache drivers
- Supports cache tagging when using supported cache drivers
- The generated cache classes are organized in model-specific subdirectories
