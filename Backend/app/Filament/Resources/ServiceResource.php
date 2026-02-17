<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Link;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Model;
class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Services';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'Services';
    protected static ?string $modelLabel = 'Service';
    protected static ?string $pluralLabel = 'Services';
    protected static ?string $label = 'Service';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Metadata')
                ->collapsible()
                ->schema([
                TextInput::make('title.en')
                    ->label('Title')
                    ->required(),
                TextInput::make('title.ar')
                    ->label('العنوان')
                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                    ->required(),
                    Textarea::make('metadata.description.en')
                            ->label('Description')
                            ->required(),
                        Textarea::make('metadata.description.ar')
                            ->label('الوصف')
                            ->required()
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        TagsInput::make('metadata.tags.en')
                            ->label('Tags'),
                        TagsInput::make('metadata.tags.ar')
                            ->label('الوسوم')
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        TextInput::make('metadata.startServiceLink.en')
                            ->label('Get Started Link')
                            ->url(),
                        TextInput::make('metadata.startServiceLink.ar')
                            ->label('بدء الخدمة')
                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                            ->url(),
                        TextInput::make('metadata.serviceLevelLink.en')
                            ->label('Service Level Link')
                            ->url(),
                        TextInput::make('metadata.serviceLevelLink.ar')
                            ->label('اتفاقية مستوى الخدمة')
                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                            ->url(),
                        Checkbox::make('is_published')
                            ->label('Published')
                            ->default(true),
                        Forms\Components\TextInput::make('order')
                            ->label('Order')
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => \App\Models\Service::max('order') + 1)
                            ->required()
                            ->minValue(1)
                            ->validationMessages([
                                'min' => 'Order must be greater than zero.',
                            ]),
                ])->columns(2)->columnSpanFull(),
                Forms\Components\Section::make('Metadata')
                    ->collapsible()
                    ->schema([
                        TextInput::make('metadata.targetAudience.en')
                            ->label('Target Audience'),
                        TextInput::make('metadata.targetAudience.ar')
                            ->label('الفئة المستهدفة')
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        TextInput::make('metadata.serviceDuration.en')
                            ->label('Service Duration'),
                        TextInput::make('metadata.serviceDuration.ar')
                            ->label('مدة الخدمة')
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        TextInput::make('metadata.serviceChannels.en')
                            ->label('Service Channels'),
                        TextInput::make('metadata.serviceChannels.ar')
                            ->label('قنوات الخدمة')
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        TextInput::make('metadata.serviceCost.en')
                            ->label('Service Cost'),
                        TextInput::make('metadata.serviceCost.ar')
                            ->label('تكلفة الخدمة')
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        
                            TextInput::make('metadata.FAQsLink.url.en')
                                ->url()
                                ->rules(['url'])
                                ->validationMessages(['url' => 'Invalid URL'])
                                ->label('FAQs Link URL'),
                            TextInput::make('metadata.FAQsLink.url.ar')
                                ->label('الاسئلة الشائعة')
                                ->rules(['url'])
                                ->validationMessages(['url' => 'ادخل رابط فقط'])
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->url(),
                            TextInput::make('metadata.phone')
                                //->rules(['regex:/^(?:\+9665\d{8}|9200\d{6})$/'])
                               // ->validationMessages(['regex' => 'Invalid Phone Number'])
                                ->label('Phone'),
                            TextInput::make('metadata.email')
                                ->rules(['email'])
                                ->validationMessages(['email' => 'Invalid Email'])
                                ->label('Email'),

                                FileUpload::make('metadata.userManual.en')
                                    ->label('User Manual')
                                    ->directory('uploads/pages/services')
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->maxSize(25000)
                                    ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),

                                FileUpload::make('metadata.userManual.ar')
                                ->label('الدليل المستخدم')
                                ->directory('uploads/pages/services')
                                ->maxSize(25000)
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),

                            Repeater::make('metadata.paymentChannels')
                            ->label('Payment Channels/قنوات الدفع')
                            ->schema([
                                TextInput::make('alt.en')
                                    ->label('Alternative Text'),
                                TextInput::make('alt.ar')
                                    ->label('النص البديل')
                                    ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                FileUpload::make('image')
                                    ->label('Image')
                                    ->directory('uploads/pages/services')
                                    ->image()
                                    ->imagePreviewHeight('80')
                                    ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName()))
                                    ->reorderable()
                                    ->required(fn ($get) => 
                                        !empty($get('alt.en')) ||
                                        !empty($get('alt.ar')) 
                                    ),
                            ])->columns(3)->columnSpanFull(),

                            Repeater::make('metadata.mobileApp')
                            ->label('Mobile App / تطبيقات الجوال')
                            ->schema([
                                TextInput::make('alt.en')
                                    ->label('Alternative Text'),
                                TextInput::make('alt.ar')
                                    ->label('النص البديل')
                                    ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                TextInput::make('url.en')
                                    ->label('URL')
                                    ->url(),
                                TextInput::make('url.ar')
                                    ->label('الرابط')
                                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                    ->url(),
                                FileUpload::make('image')
                                    ->label('Image')
                                    ->directory('uploads/pages/services')
                                    ->image()
                                    ->imagePreviewHeight('80')
                                    ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName()))
                                    ->required(fn ($get) => 
                                        !empty($get('alt.en')) ||
                                        !empty($get('alt.ar')) ||
                                        !empty($get('url.en')) ||
                                        !empty($get('url.ar'))
                                    ),
                                   
                            ])->columns(2)->columnSpanFull(),
                    ])->columns(2)->columnSpanFull(),



                    Forms\Components\Section::make('Content')
                    ->collapsible()
                    ->label('Content')
                    ->schema([
                    Forms\Components\Tabs::make('Steps')
                    ->label('Steps')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Steps')
                                ->label('Steps/الخطوات')
                                ->schema([
                                    RichEditor::make('content.steps.en')
                                        ->label('Steps'),
                                    RichEditor::make('content.steps.ar')
                                        ->label('الخطوات')
                                        ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                ])->columns(1)->columnSpanFull(),
                                Forms\Components\Tabs\Tab::make('conditions')
                                ->label('Conditions/شروط الاستخدام')
                                ->schema([
                                    RichEditor::make('content.conditions.en')
                                        ->label('Conditions'),
                                    RichEditor::make('content.conditions.ar')
                                        ->label('شروط الاستخدام')
                                        ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                ])->columns(1)->columnSpanFull(),
                                Forms\Components\Tabs\Tab::make('requiredDocuments')
                                ->label('Required Documents/المستندات المطلوبة')
                                ->schema([
                                    RichEditor::make('content.requiredDocuments.en')
                                        ->label('Required Documents'),
                                    RichEditor::make('content.requiredDocuments.ar')
                                        ->label('المستندات المطلوبة')
                                        ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                ])->columns(1)->columnSpanFull(),
                        ])->columns(1)->columnSpanFull(),
                    ])->columns(2)->columnSpanFull(),

                    Forms\Components\Section::make('Related Services')
                    ->collapsible()
                    ->label('Related Services')
                    ->schema([
                        TextInput::make('relatedServices.title.en')
                            ->label('Title'),
                        TextInput::make('relatedServices.title.ar')
                            ->label('العنوان')
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                            Textarea::make('relatedServices.description.en')
                            ->label('Description'),
                        Textarea::make('relatedServices.description.ar')
                            ->label('الوصف')
                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                        Repeater::make('relatedServices.list')
                        ->reactive()
                            ->label('Services')
                            ->schema([
                                TextInput::make('title.en')
                                    ->reactive()
                                    ->required()
                                    ->label('Title'),
                                TextInput::make('title.ar')
                                    ->label('العنوان')
                                    ->required()
                                    ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                Textarea::make('description.en')
                                    ->required()
                                    ->label('Description'),
                                Textarea::make('description.ar')
                                    ->label('الوصف')
                                    ->required()
                                    ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                TagsInput::make('tags.en')
                                    ->required()
                                    ->label('Tags'),
                                TagsInput::make('tags.ar')
                                    ->label('الوسوم')
                                    ->required()
                                    ->extraFieldWrapperAttributes(['class' => 'text-right']),
                                FileUpload::make('icon')
                                    ->label('Service Icon')
                                    ->directory('uploads/pages/services')
                                    ->image()
                                    ->imagePreviewHeight('80')
                                    ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                                    Fieldset::make('Main Action')
                                    ->schema([
                                        TextInput::make('main_action.title.en')
                                            ->label('Title'),
                
                                        TextInput::make('main_action.title.ar')
                                            ->label('العنوان')
                                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                
                                        TextInput::make('main_action.url.en')
                                            ->label('URL')
                                            ->url(),
                                            TextInput::make('main_action.url.ar')
                                            ->label('الرابط')
                                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                            ->url()
                                    ])->columns(4),
                                    Fieldset::make('Secondary Action')
                                    ->schema([
                                        TextInput::make('secondary_action.title.en')
                                            ->label('Title'),
                
                                        TextInput::make('secondary_action.title.ar')
                                            ->label('العنوان')
                                            ->extraFieldWrapperAttributes(['class' => 'text-right']),
                
                                        TextInput::make('secondary_action.url.en')
                                            ->label('URL')
                                            ->url(),
                                            TextInput::make('secondary_action.url.ar')
                                            ->label('الرابط')
                                            ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                            ->url()
                                    ])->columns(4),
                            ])->columns(2)->columnSpanFull(),
                        ])->columns(2)->columnSpanFull(),


               
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')->label('Title'),
                
                \Filament\Tables\Columns\ToggleColumn::make('is_published')
                ->label('Published')
                ->disabled(fn () => ! auth()->user()?->can('update Service'))
                ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('created_at')->label('Created At'),
                \Filament\Tables\Columns\TextColumn::make('updated_at')->label('Last Updated'),
                \Filament\Tables\Columns\TextColumn::make('order')->label('Order')->badge()->searchable(),
                // \Filament\Tables\Columns\TextInputColumn::make('order')
                //     ->label('Order')
                //     ->rules(['required', 'integer', 'min:1', 'unique:services,order'])
                //     ->grow(false)
                //     ->extraAttributes(['style' => 'width: 80px; text-align: center;'])
                //     ->sortable()
                //     ->width('50px')
                //     ->disabled(fn () => ! auth()->user()?->can('update Service')),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('is_published')->label('Published')->options([
                    true => 'Published',
                    false => 'Unpublished',
                ]),
                
               
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->requiresConfirmation(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])  
            ->defaultSort('updated_at', 'desc')
            ;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('order', 'asc');
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->orderBy('order', 'asc');
    }
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Service') ?? false;
    }
    public static function canCreate(): bool
    {
       return auth()->user()?->can('create Service') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update Service') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Service') ?? false;
    }
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete Service') ?? false;
    }

}
