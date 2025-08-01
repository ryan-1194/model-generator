# make:repository-interface Command Documentation

## Overview

The `make:repository-interface` command creates repository interface contracts that define the methods available for repository implementations, supporting the Repository pattern with proper interface segregation.

## Command Signature

```bash
php artisan make:repository-interface {name} {model} [options]
```

## Description

Create a new repository interface that extends the base RepositoryInterface. This command is typically called automatically by the `make:repository` command but can also be used standalone to create interfaces for existing repositories or when following interface-first development.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `name` | Required | The name of the repository interface |
| `model` | Required | The model name associated with the repository |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--force` | -f | Flag | Create the class even if the interface already exists |

## How It Works

1. **Interface Creation**: Generates a repository interface that extends the base RepositoryInterface
2. **Model Integration**: Uses the HasModel trait for model-related functionality
3. **Naming Convention**: Automatically appends "RepositoryInterface" if not provided
4. **Namespace**: Places interface in `App\Repositories\Contracts` namespace

## Usage Examples

### Basic Interface Creation

```bash
php artisan make:repository-interface UserRepositoryInterface User
```
*Creates UserRepositoryInterface for User model*

### Interface with Custom Name

```bash
php artisan make:repository-interface CustomUserInterface User
```
*Creates CustomUserInterface (automatically becomes CustomUserInterfaceRepositoryInterface)*

### Force Overwrite Existing Interface

```bash
php artisan make:repository-interface UserRepositoryInterface User --force
```

### Standalone Interface Creation

```bash
php artisan make:repository-interface ProductRepositoryInterface Product
```
*Creates interface without creating repository class*

## Generated Interface Structure

The generated interface extends the base RepositoryInterface:

```php
<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface extends RepositoryInterface
{
    // Add custom method signatures here
}
```

## Base RepositoryInterface Methods

All generated interfaces extend the base RepositoryInterface which includes:

```php
<?php

namespace App\Repositories\Contracts;

interface RepositoryInterface
{
    // Basic CRUD operations
    public function all(array $columns = ['*']);
    public function find($id, array $columns = ['*']);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);

    // Query building
    public function where($column, $operator = null, $value = null);
    public function whereIn($column, array $values);
    public function orderBy($column, $direction = 'asc');
    public function limit($limit);
    public function offset($offset);

    // Relationships
    public function with($relations);
    public function load($relations);

    // Pagination
    public function paginate($perPage = 15, array $columns = ['*']);

    // Counting and existence
    public function count();
    public function exists();

    // Advanced queries
    public function findWhere(array $where, array $columns = ['*']);
    public function findWhereIn($field, array $where, array $columns = ['*']);
    public function findWhereNotIn($field, array $where, array $columns = ['*']);
}
```

## Naming Conventions

The command follows consistent naming patterns:

| Input Name | Generated Interface Name |
|------------|-------------------------|
| `User` | `UserRepositoryInterface` |
| `UserRepository` | `UserRepositoryInterface` |
| `UserRepositoryInterface` | `UserRepositoryInterface` |
| `CustomUser` | `CustomUserRepositoryInterface` |

## File Location

Generated interfaces are placed in:
```
app/Repositories/Contracts/{InterfaceName}.php
```

## Custom Method Definitions

Add custom method signatures to your interface for model-specific operations:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by email address
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get all active users
     */
    public function getActiveUsers(): Collection;

    /**
     * Get users with their posts loaded
     */
    public function getUsersWithPosts(): Collection;

    /**
     * Search users by name or email
     */
    public function searchUsers(string $query): Collection;

    /**
     * Get users created within date range
     */
    public function getUsersCreatedBetween(\DateTime $start, \DateTime $end): Collection;

    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin(int $userId): bool;

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): Collection;
}
```

## Interface Implementation

Once the interface is created, implement it in your repository class:

```php
<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function getActiveUsers(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    public function getUsersWithPosts(): Collection
    {
        return $this->model->with('posts')->get();
    }

    public function searchUsers(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();
    }

    public function getUsersCreatedBetween(\DateTime $start, \DateTime $end): Collection
    {
        return $this->model
            ->whereBetween('created_at', [$start, $end])
            ->get();
    }

    public function updateLastLogin(int $userId): bool
    {
        return $this->model
            ->where('id', $userId)
            ->update(['last_login_at' => now()]) > 0;
    }

    public function getUsersByRole(string $role): Collection
    {
        return $this->model->where('role', $role)->get();
    }
}
```

## Service Provider Binding

Register the interface binding in your service provider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepository;
use App\Repositories\Contracts\UserRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
```

## Testing with Interfaces

Interfaces make testing easier by allowing easy mocking:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Mockery;

class UserServiceTest extends TestCase
{
    public function test_user_service_can_find_user_by_email()
    {
        // Mock the repository interface
        $mockRepository = Mockery::mock(UserRepositoryInterface::class);
        
        $user = new User(['id' => 1, 'email' => 'test@example.com']);
        
        $mockRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        // Bind mock to container
        $this->app->instance(UserRepositoryInterface::class, $mockRepository);

        // Test your service that depends on the repository
        $service = app(UserService::class);
        $result = $service->findUserByEmail('test@example.com');

        $this->assertEquals($user, $result);
    }
}
```

## Interface-First Development

Use this command to create interfaces before implementations:

1. **Define Interface First**:
```bash
php artisan make:repository-interface UserRepositoryInterface User
```

2. **Add Method Signatures**:
```php
interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function getActiveUsers(): Collection;
}
```

3. **Create Implementation**:
```bash
php artisan make:repository User
```

4. **Implement Methods**:
```php
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    // Implement interface methods
}
```

## Integration with Other Commands

The interface command integrates with:

- `make:repository` - Automatically called to create interfaces
- `make:custom-model` - Repository interfaces created when --repository flag used
- `make:cache` - Validates interface existence for cache classes

## Best Practices

1. **Method Documentation**: Always document interface methods with PHPDoc
2. **Return Types**: Use specific return types for better IDE support
3. **Parameter Types**: Type-hint parameters for better code clarity
4. **Single Responsibility**: Keep interfaces focused on specific concerns
5. **Consistent Naming**: Follow consistent naming conventions
6. **Version Compatibility**: Consider backward compatibility when modifying interfaces

## Error Handling

The command handles various scenarios:

- **Existing Interface**: Prevents overwriting unless --force is used
- **Invalid Names**: Validates interface and model names
- **Directory Creation**: Automatically creates Contracts directory if needed
- **Naming Conflicts**: Handles interface naming edge cases

## Advanced Interface Patterns

### Repository with Multiple Interfaces

```php
interface UserRepositoryInterface extends RepositoryInterface
{
    // Basic user operations
}

interface UserSearchInterface
{
    public function searchUsers(string $query): Collection;
    public function searchByRole(string $role): Collection;
}

interface UserStatisticsInterface
{
    public function getUserCount(): int;
    public function getActiveUserCount(): int;
    public function getUserRegistrationStats(): array;
}

// Implementation
class UserRepository extends BaseRepository implements 
    UserRepositoryInterface, 
    UserSearchInterface, 
    UserStatisticsInterface
{
    // Implement all interface methods
}
```

## Related Commands

- `make:repository` - Create repository classes (automatically creates interfaces)
- `make:custom-model` - Create enhanced models (can include repository interfaces)
- `make:cache` - Create cache classes (requires repository interfaces)

## Notes

- Uses stub file located in `app/CustomGenerator/stubs/interface.stub`
- Automatically appends "RepositoryInterface" to names if not present
- Uses HasModel trait for model-related functionality
- Placed in `App\Repositories\Contracts` namespace
- Compatible with Laravel's dependency injection container
- Follows PSR-4 autoloading standards
- Supports interface inheritance and composition patterns
