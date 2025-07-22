<?php

namespace App\Providers;

use App\Cache\Console\GenerateCacheCommand;
use App\CustomModelGenerator\Console\CustomModelMakeCommand;
use App\CustomModelGenerator\Console\CustomModelFromTableCommand;
use App\Console\Commands\GenerateModelCommand;
use App\ModelGenerator\Console\GenerateCustomModel;
use App\Repositories\Console\GenerateRepository;
use App\Repositories\Console\GenerateRepositoryInterface;
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

        $repositoryFiles = File::files($repositoryPath);

        foreach ($repositoryFiles as $repositoryFile) {
            $className = pathinfo($repositoryFile, PATHINFO_FILENAME);
            $class = $namespace.$className;

            if (class_exists($class) && ! ($reflector = new \ReflectionClass($class))->isAbstract()) {
                $interfaces = $reflector->getInterfaces();
                $directInterface = next($interfaces);
                $interfaceName = $directInterface->getName();

                if (interface_exists($interfaceName)) {
                    $this->app->bind($interfaceName, $class);
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
