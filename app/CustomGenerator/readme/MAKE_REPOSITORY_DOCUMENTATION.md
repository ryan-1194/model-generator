# make:repository Command Documentation

## Overview

The `make:repository` command creates repository classes and interfaces following the Repository pattern, providing a clean abstraction layer between your models and business logic.

## Command Signature

```bash
php artisan make:repository {model} [name] [interface] [options]
```

## Description

Create a new repository class and its corresponding interface for a specific model. This command implements the Repository pattern by generating both the repository implementation and its interface, along with base repository classes on first run.

## Arguments

| Argument | Type | Description |
|----------|------|-------------|
| `model` | Required | The model name associated with the repository |
| `name` | Optional | The name of the repository (defaults to {Model}Repository) |
| `interface` | Optional | The name of the repository interface (defaults to {Model}RepositoryInterface) |

## Options

| Option | Short | Type | Description |
|--------|-------|------|-------------|
| `--force` | -f | Flag | Create the class even if the repository already exists |

## How It Works

1. **Base Files Generation**: Creates base repository files on first run (BaseRepository, RepositoryInterface)
2. **Repository Creation**: Generates model-specific repository class
3. **Interface Generation**: Automatically calls `make:repository-interface` to create the interface
4. **Directory Structure**: Creates proper directory structure for repositories and contracts

## First Run Setup

On the first execution, the command automatically generates base files:

### BaseRepository (`app/Repositories/BaseRepository.php`)
- Abstract base class with common repository methods
- Implements basic CRUD operations
- Provides foundation for all repository classes

### RepositoryInterface (`app/Repositories/Contracts/RepositoryInterface.php`)
- Base interface defining common repository methods
- Includes methods like `all()`, `find()`, `create()`, `update()`, `delete()`
- Provides contract for all repository implementations

## Usage Examples

### Basic Repository Creation

```bash
php artisan make:repository User
```
*Creates UserRepository class and UserRepositoryInterface*

### Repository with Custom Name

```bash
php artisan make:repository User CustomUserRepository
```
*Creates CustomUserRepository class and CustomUserRepositoryInterface*

### Repository with Custom Interface Name

```bash
php artisan make:repository Product ProductRepository ProductRepositoryContract
```
*Creates ProductRepository class and ProductRepositoryContract interface*

### Force Overwrite Existing Repository

```bash
php artisan make:repository User --force
```

## Generated Directory Structure

```
app/
├── Repositories/
│   ├── BaseRepository.php
│   ├── UserRepository.php
│   ├── ProductRepository.php
│   └── Contracts/
│       ├── RepositoryInterface.php
│       ├── UserRepositoryInterface.php
│       └── ProductRepositoryInterface.php
```

## Generated Repository Class

The generated repository class extends BaseRepository and implements the model-specific interface:

```php
<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    // Add custom repository methods here
}
```

## Generated Interface

The generated interface extends the base RepositoryInterface:

```php
<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface extends RepositoryInterface
{
    // Add custom method signatures here
}
```

## BaseRepository Methods

The BaseRepository provides common methods that all repositories inherit:

```php
// Basic CRUD operations
public function all(array $columns = ['*']);
public function find($id, array $columns = ['*']);
public function create(array $data);
public function update($id, array $data);
public function delete($id);

// Query methods
public function where($column, $operator = null, $value = null);
public function whereIn($column, array $values);
public function orderBy($column, $direction = 'asc');
public function limit($limit);
public function offset($offset);

// Relationship methods
public function with($relations);
public function load($relations);

// Pagination
public function paginate($perPage = 15, array $columns = ['*']);

// Counting
public function count();
public function exists();

// Advanced queries
public function findWhere(array $where, array $columns = ['*']);
public function findWhereIn($field, array $where, array $columns = ['*']);
public function findWhereNotIn($field, array $where, array $columns = ['*']);
```

## Service Provider Registration

Register repositories in your service provider for dependency injection:

```php
// app/Providers/RepositoryServiceProvider.php
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

## Usage in Controllers

Inject repositories into controllers for clean separation of concerns:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserController extends Controller
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        $users = $this->userRepository->all();
        return view('users.index', compact('users'));
    }

    public function show($id)
    {
        $user = $this->userRepository->find($id);
        return view('users.show', compact('user'));
    }

    public function store(Request $request)
    {
        $user = $this->userRepository->create($request->validated());
        return redirect()->route('users.show', $user->id);
    }
}
```

## Custom Repository Methods

Add custom methods to your repository for specific business logic:

```php
<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

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

    public function getActiveUsers()
    {
        return $this->model->where('is_active', true)->get();
    }

    public function getUsersWithPosts()
    {
        return $this->model->with('posts')->get();
    }

    public function searchUsers(string $query)
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();
    }
}
```

## Interface Definition

Update the interface to include custom method signatures:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function getActiveUsers(): Collection;
    public function getUsersWithPosts(): Collection;
    public function searchUsers(string $query): Collection;
}
```

## Testing Repositories

Repositories make testing easier by providing a mockable interface:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository(new User());
    }

    public function test_can_create_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ];

        $user = $this->repository->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
    }

    public function test_can_find_user_by_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $foundUser = $this->repository->findByEmail('test@example.com');

        $this->assertEquals($user->id, $foundUser->id);
    }
}
```

## Integration with Other Commands

The repository command integrates with:

- `make:custom-model` - Can be called automatically with --repository flag
- `make:custom-model-from-table` - Uses repository pattern for data access
- `make:repository-interface` - Automatically called to create interfaces
- `make:cache` - Validates repository existence before creating cache classes

## Best Practices

1. **Interface First**: Always define interface methods before implementation
2. **Single Responsibility**: Keep repositories focused on data access only
3. **Dependency Injection**: Use interfaces for dependency injection
4. **Custom Methods**: Add model-specific methods to repositories
5. **Testing**: Mock interfaces in tests, not concrete classes
6. **Service Layer**: Use services for complex business logic, repositories for data access

## Error Handling

The command handles various scenarios:

- **Interface Regeneration**: Prompts to regenerate existing interfaces
- **Model Validation**: Checks if the specified model exists
- **Directory Creation**: Automatically creates necessary directories
- **Naming Conflicts**: Handles repository and interface naming conflicts

## Related Commands

- `make:repository-interface` - Create standalone repository interfaces
- `make:custom-model` - Create enhanced models (can include repositories)
- `make:cache` - Create cache classes (requires repositories)
- `make:service` - Create service classes (work well with repositories)

## Notes

- Uses stub files located in `app/CustomGenerator/stubs/repository.stub`
- Automatically creates base files (BaseRepository, RepositoryInterface) on first run
- Repository classes extend BaseRepository for common functionality
- Interfaces extend base RepositoryInterface for consistency
- Compatible with Laravel's dependency injection container
- Follows Laravel naming conventions and directory structure
- The HasModel trait is used for model-related functionality
