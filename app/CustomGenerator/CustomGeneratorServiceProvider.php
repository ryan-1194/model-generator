<?php

namespace App\CustomGenerator;

use App\CustomGenerator\Console\CustomFormRequestMakeCommand;
use App\CustomGenerator\Console\CustomMigrationMakeCommand;
use App\CustomGenerator\Console\CustomModelFromTableCommand;
use App\CustomGenerator\Console\CustomModelMakeCommand;
use App\CustomGenerator\Console\GenerateCacheCommand;
use App\CustomGenerator\Console\GenerateRepository;
use App\CustomGenerator\Console\GenerateRepositoryInterface;
use App\CustomGenerator\Services\DatabaseColumnReaderService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class CustomGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the DatabaseColumnReaderService as a singleton
//        $this->app->singleton(DatabaseColumnReaderService::class, function ($app) {
//            return new DatabaseColumnReaderService();
//        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRepositories();
        $this->registerCommands();
    }

    /**
     * Register all CustomGenerator commands
     */
    protected function registerCommands(): void
    {
        $this->commands([
            GenerateRepository::class,
            GenerateRepositoryInterface::class,
            GenerateCacheCommand::class,
            CustomModelMakeCommand::class,
            CustomModelFromTableCommand::class,
            CustomMigrationMakeCommand::class,
            CustomFormRequestMakeCommand::class,
        ]);
    }

    /**
     * Register repository interfaces with their implementations
     */
    protected function registerRepositories(): void
    {
        $namespace = 'App\\Repositories\\';
        $repositoryPath = app_path('Repositories');

        if (!File::exists($repositoryPath)) {
            return;
        }

        $repositoryFiles = File::files($repositoryPath);

        foreach ($repositoryFiles as $repositoryFile) {
            $className = pathinfo($repositoryFile, PATHINFO_FILENAME);
            $class = $namespace.$className;

            // Skip if class doesn't exist or is abstract
            if (!class_exists($class)) {
                continue;
            }

            try {
                $reflector = new \ReflectionClass($class);

                // Skip abstract classes
                if ($reflector->isAbstract()) {
                    continue;
                }

                $interfaces = $reflector->getInterfaces();

                // Skip if no interfaces are implemented
                if (empty($interfaces)) {
                    continue;
                }

                // Get the first interface (most specific one)
                $directInterface = reset($interfaces);

                // Validate that we have a valid interface object
                if ($directInterface && $directInterface instanceof \ReflectionClass) {
                    $interfaceName = $directInterface->getName();

                    // Only bind if the interface exists and is actually an interface
                    if (interface_exists($interfaceName)) {
                        $this->app->bind($interfaceName, $class);
                    }
                }
            } catch (\ReflectionException $e) {
                // Skip repositories that cause reflection errors
                continue;
            } catch (\Throwable $e) {
                // Skip repositories that cause any other errors
                continue;
            }
        }
    }
}
