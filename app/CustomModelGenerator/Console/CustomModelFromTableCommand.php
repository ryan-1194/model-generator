<?php

namespace App\CustomModelGenerator\Console;

use App\CustomModelGenerator\Services\DatabaseColumnReaderService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;

class CustomModelFromTableCommand extends CustomModelMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:custom-model-from-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom model with enhanced features from existing database table';

    /**
     * Cached columns to avoid multiple prompts
     *
     * @var array|null
     */
    protected $cachedColumns = null;

    /**
     * The database column reader service.
     */
    protected DatabaseColumnReaderService $columnReader;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files, DatabaseColumnReaderService $columnReader)
    {
        parent::__construct($files);
        $this->columnReader = $columnReader;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the model name
        $modelName = $this->getNameInput();

        // Check if model name is reserved
        if ($this->isReservedName($modelName)) {
            $this->error('The name "'.$modelName.'" is reserved by PHP.');

            return false;
        }

        // Check if the class already exists
        if ((! $this->hasOption('force') ||
             ! $this->option('force')) &&
             $this->alreadyExists($modelName)) {
            $this->error($this->type.' already exists!');

            return false;
        }

        // Get table name - either from option or derive from model name
        $tableName = Str::snake(Str::pluralStudly($modelName));

        // Check if table exists
        if (! Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist in the database.");

            return false;
        }

        info("Reading columns from table: {$tableName}");

        // Read columns from database table
        try {
            $this->cachedColumns = $this->columnReader->getTableColumns($tableName);
        } catch (\RuntimeException $e) {
            error($e->getMessage());
            return false;
        }

        if (empty($this->cachedColumns)) {
            $this->error("No columns found in table '{$tableName}'.");

            return false;
        }

        info('Found '.count($this->cachedColumns)." columns in table '{$tableName}'");

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('api', true);
            $this->input->setOption('requests', true);
            $this->input->setOption('repository', true);
            $this->input->setOption('json-resource', true);
            $this->input->setOption('cache', true);
        }

        // Create custom model file with enhanced features
        $this->createCustomModelFile();

        // Handle other options that parent would normally handle
        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        }

        if ($this->option('requests')) {
            $this->createCustomFormRequests();
        }

        if ($this->repositoryCommandExists() && $this->option('repository')) {
            $this->createRepository();
        }

        if ($this->cacheCommandExists() && $this->option('cache')) {
            $this->createCache();
        }

        if ($this->option('json-resource')) {
            $this->createJsonResource();
        }

        $this->info('Custom model created successfully with columns from database table!');

        return 0;
    }


    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->isReservedName($this->getNameInput()) || $this->didReceiveOptions($input)) {
            return;
        }

        $options = [
            'seed' => 'Database Seeder',
            'factory' => 'Factory',
            'requests' => 'Form Requests',
            'policy' => 'Policy',
            'api' => 'API Controller',
            'json-resource' => 'JSON Resource',
            'soft-deletes' => 'Soft Deletes',
        ];

        if ($this->repositoryCommandExists()) {
            $options['repository'] = 'Repository';
        }

        if ($this->cacheCommandExists()) {
            $options['cache'] = 'Cache';
        }

        (new Collection(multiselect('Would you like any of the following?', $options)))->each(fn ($option) => $input->setOption($option, true));
    }
}
