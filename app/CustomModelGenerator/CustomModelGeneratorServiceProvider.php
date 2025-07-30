<?php

namespace App\CustomModelGenerator;

use App\CustomModelGenerator\Console\CustomModelFromTableCommand;
use App\CustomModelGenerator\Console\CustomModelMakeCommand;
use App\CustomModelGenerator\Console\GenerateCacheCommand;
use App\CustomModelGenerator\Console\GenerateRepository;
use App\CustomModelGenerator\Console\GenerateRepositoryInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class CustomModelGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
     * Register all CustomModelGenerator commands
     */
    protected function registerCommands(): void
    {
        $this->commands([
            GenerateRepository::class,
            GenerateRepositoryInterface::class,
            GenerateCacheCommand::class,
            CustomModelMakeCommand::class,
            CustomModelFromTableCommand::class,
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
