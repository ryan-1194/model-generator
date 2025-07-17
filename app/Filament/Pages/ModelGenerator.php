<?php

namespace App\Filament\Pages;

use App\Services\ModelGeneratorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class ModelGenerator extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static string $view = 'filament.pages.model-generator';

    protected static ?string $title = 'Model Generator';

    protected static ?string $navigationLabel = 'Model Generator';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Model Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('model_name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if ($state) {
                                    $set('table_name', Str::snake(Str::plural($state)));
                                    // Set default filenames
                                    $set('factory_name', $state . 'Factory');
                                    $set('policy_name', $state . 'Policy');
                                    $set('resource_controller_name', $state . 'Controller');
                                    $set('json_resource_name', $state . 'Resource');
                                    $set('api_controller_name', $state . 'ApiController');
                                    $set('form_request_name', $state . 'Request');
                                    $set('repository_name', $state . 'Repository');
                                }
                            })
                            ->helperText('Use StudlyCase (e.g., BlogPost)'),

                        Forms\Components\TextInput::make('table_name')
                            ->helperText('Leave empty to auto-generate from model name'),

                        Forms\Components\Toggle::make('generate_migration')
                            ->default(true)
                            ->helperText('Generate migration file'),

                        Forms\Components\Toggle::make('has_timestamps')
                            ->default(true)
                            ->helperText('Include created_at and updated_at columns'),

                        Forms\Components\Toggle::make('has_soft_deletes')
                            ->default(false)
                            ->helperText('Include soft delete functionality'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('File Generation Options')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('generate_factory')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Generate model factory'),

                                Forms\Components\TextInput::make('factory_name')
                                    ->placeholder(fn (Forms\Get $get): string => ($get('model_name') ?: 'Model') . 'Factory')
                                    ->helperText('Default: {ModelName}Factory')
                                    ->visible(fn (Forms\Get $get): bool => $get('generate_factory')),

                                Forms\Components\Toggle::make('generate_policy')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Generate model policy'),

                                Forms\Components\TextInput::make('policy_name')
                                    ->placeholder(fn (Forms\Get $get): string => ($get('model_name') ?: 'Model') . 'Policy')
                                    ->helperText('Default: {ModelName}Policy')
                                    ->visible(fn (Forms\Get $get): bool => $get('generate_policy')),

                                Forms\Components\Toggle::make('generate_resource_controller')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Generate resource controller'),

                                Forms\Components\TextInput::make('resource_controller_name')
                                    ->placeholder(fn (Forms\Get $get): string => ($get('model_name') ?: 'Model') . 'Controller')
                                    ->helperText('Default: {ModelName}Controller')
                                    ->visible(fn (Forms\Get $get): bool => $get('generate_resource_controller')),

                                Forms\Components\Toggle::make('generate_json_resource')
                                    ->default(false)
                                    ->live()
                                    ->helperText('Generate JSON resource'),

                                Forms\Components\TextInput::make('json_resource_name')
                                    ->placeholder(fn (Forms\Get $get): string => ($get('model_name') ?: 'Model') . 'Resource')
                                    ->helperText('Default: {ModelName}Resource')
                                    ->visible(fn (Forms\Get $get): bool => $get('generate_json_resource')),

                                Forms\Components\Toggle::make('generate_api_controller')
                                    ->default(false)
                                    ->live()
                                    ->helperText('Generate API controller'),

                                Forms\Components\TextInput::make('api_controller_name')
                                    ->placeholder(fn (Forms\Get $get): string => ($get('model_name') ?: 'Model') . 'ApiController')
                                    ->helperText('Default: {ModelName}ApiController')
                                    ->visible(fn (Forms\Get $get): bool => $get('generate_api_controller')),

                                Forms\Components\Toggle::make('generate_form_request')
                                    ->default(false)
                                    ->live()
                                    ->helperText('Generate form request'),

                                Forms\Components\TextInput::make('form_request_name')
                                    ->placeholder(fn (Forms\Get $get): string => ($get('model_name') ?: 'Model') . 'Request')
                                    ->helperText('Default: {ModelName}Request')
                                    ->visible(fn (Forms\Get $get): bool => $get('generate_form_request')),

                                Forms\Components\Toggle::make('generate_repository')
                                    ->default(false)
                                    ->live()
                                    ->helperText('Generate repository and interface'),

                                Forms\Components\TextInput::make('repository_name')
                                    ->placeholder(fn (Forms\Get $get): string => ($get('model_name') ?: 'Model') . 'Repository')
                                    ->helperText('Default: {ModelName}Repository')
                                    ->visible(fn (Forms\Get $get): bool => $get('generate_repository')),
                            ]),
                    ]),

                Forms\Components\Section::make('Columns')
                    ->schema([
                        Forms\Components\Repeater::make('columns')
                            ->schema([
                                Forms\Components\TextInput::make('column_name')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\Select::make('data_type')
                                    ->required()
                                    ->options([
                                        'string' => 'String',
                                        'text' => 'Text',
                                        'integer' => 'Integer',
                                        'bigInteger' => 'Big Integer',
                                        'boolean' => 'Boolean',
                                        'date' => 'Date',
                                        'datetime' => 'DateTime',
                                        'timestamp' => 'Timestamp',
                                        'decimal' => 'Decimal',
                                        'float' => 'Float',
                                        'json' => 'JSON',
                                    ])
                                    ->columnSpan(2),

                                Forms\Components\Toggle::make('nullable')
                                    ->default(false),

                                Forms\Components\Toggle::make('unique')
                                    ->default(false),

                                Forms\Components\TextInput::make('default_value')
                                    ->columnSpan(2),

                                Forms\Components\Toggle::make('is_fillable')
                                    ->default(true)
                                    ->helperText('Include in model fillable array'),
                            ])
                            ->columns(4)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['column_name'] ?? null)
                            ->addActionLabel('Add Column')
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    public ?array $previews = null;

    public function preview()
    {
        $generator = new ModelGeneratorService();
        $this->previews = $generator->previewFromFormData($this->data);
    }

    public function generate()
    {
        $generator = new ModelGeneratorService();
        $result = $generator->generateFromFormData($this->data);

        if ($result['success']) {
            Notification::make()
                ->title('Success!')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error!')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }
}
