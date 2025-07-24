<?php

namespace App\Providers;

use App\CustomModelGenerator\Console\GenerateCacheCommand;
use App\Console\Commands\GenerateModelCommand;
use App\CustomModelGenerator\Console\CustomModelFromTableCommand;
use App\CustomModelGenerator\Console\CustomModelMakeCommand;
use App\CustomModelGenerator\Console\GenerateRepository;
use App\CustomModelGenerator\Console\GenerateRepositoryInterface;
use App\ModelGenerator\Console\GenerateCustomModel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
    }

    protected function registerRepositories(): void
    {
        $namespace = 'App\\Repositories\\';
        $repositoryPath = app_path('Repositories');

        if (File::exists($repositoryPath)) {
            $repositoryFiles = File::files($repositoryPath);

            foreach ($repositoryFiles as $repositoryFile) {
                $className = pathinfo($repositoryFile, PATHINFO_FILENAME);
                $class = $namespace.$className;

                // Skip if class doesn't exist or is abstract
                if (! class_exists($class)) {
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

        $this->commands(GenerateRepository::class);
        $this->commands(GenerateRepositoryInterface::class);
        $this->commands(GenerateModelCommand::class);
        $this->commands(GenerateCustomModel::class);
        $this->commands(CustomModelMakeCommand::class);
        $this->commands(CustomModelFromTableCommand::class);
        $this->commands(GenerateCacheCommand::class);
    }
}
