<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Builder\Block\BlockSchema;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use App\Models\LandingPage as LandingPageModel;
use App\Http\Resources\LandingPageResource;
use Illuminate\Http\Request;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;

class LandingPage extends Page
{
    public ?array $data = [];
    protected static ?string $title = 'Landing Page';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.landing-page';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 29;

    public function mount(): void
    {
        $this->form->fill(LandingPageModel::first()?->toArray() ?? []);
    }

    public function form(Form $form): Form
    {
        return $form
        ->schema([
            TextInput::make('title')
            ->label('Title')
            ->required(),
            Toggle::make('government_verification_banner_enabled')
                ->label('Government Verification Banner')
                ->helperText('Enable/disable the government verification banner at the top of the website header')
                ->default(false)
                ->live()
                ->columnSpanFull(),
            TextInput::make('dga_registration_number')
                ->label('DGA Registration Number')
                ->helperText('Enter the DGA registration number to display on the banner')
                ->required(fn ($get) => $get('government_verification_banner_enabled') == true)
                ->visible(fn ($get) => $get('government_verification_banner_enabled') == true)
                ->columnSpanFull(),
            TextInput::make('dga_certificate_url')
                ->label('DGA Certificate Verification URL')
                ->helperText('Enter the URL where users will be redirected when clicking the banner')
                ->url()
                ->required(fn ($get) => $get('government_verification_banner_enabled') == true)
                ->visible(fn ($get) => $get('government_verification_banner_enabled') == true)
                ->columnSpanFull(),
            Builder::make('content')
            ->columnSpanFull()
->blocks([
    // Seaction 1 block
    Builder\Block::make('banner')
    ->label('Hero Section')
    ->icon('heroicon-m-bars-3-bottom-left')
    ->schema([
        Repeater::make('items')
            ->label('Banners')
            ->schema([
                TextInput::make('title.en')
                    ->label('Title')
                    ->required(),

                TextInput::make('title.ar')
                    ->label('العنوان')
                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                    ->required(),

                Textarea::make('text.en')
                    ->label('Paragraph')
                    ->autosize()
                    ->required(),

                Textarea::make('text.ar')
                    ->label('الفقرة')
                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                    ->autosize()
                    ->required(),
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

                FileUpload::make('image.en')
                    ->label('Image')
                    ->directory('uploads/pages')
                    ->image()
                    ->imagePreviewHeight('100')
                    ->maxFiles(1)
                    ->required()
                    ->getUploadedFileNameForStorageUsing(
                        fn ($file) => (string) str($file->hashName())
                    ),

                FileUpload::make('image.ar')
                    ->label('الصورة')
                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                    ->directory('uploads/pages')
                    ->image()
                    ->imagePreviewHeight('100')
                    ->maxFiles(1)
                    ->required()
                    ->getUploadedFileNameForStorageUsing(
                        fn ($file) => (string) str($file->hashName())
                    ),
            ])
            ->columns(2)
            ->cloneable()
            ->deleteAction(
                fn (Action $action) => $action->requiresConfirmation()
            ),
    ])
    ->columns(1),
    // Seaction 2 block
            Builder\Block::make('about')
            ->label('About Section')
            ->icon('heroicon-m-bars-3-bottom-left')
            ->schema([
                TextInput::make('title.en')
                    ->label('Title')
                    ->required(),
                TextInput::make('title.ar')
                    ->label('العنوان')
                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                    ->required(),
                    Textarea::make('text.en')
                    ->autosize()
                    ->label('Paragraph')
                    ->required(),
                Textarea::make('text.ar')
                        ->label('الفقرة')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->autosize()
                        ->required(),
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
                Repeater::make('list')
                ->columnSpanFull()
                    ->schema([
                        TextInput::make('title.en')->label('Title')->required(),
                        TextInput::make('title.ar')->label('العنوان')->required()->extraFieldWrapperAttributes(['class' => 'text-right']),
                        FileUpload::make('icon')
                        ->label('Icon')
                        ->directory('uploads/pages')
                        ->imageEditor()
                        ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                        TextInput::make('number')->required(),
                    ])
                    ->collapsible()
                    ->cloneable()
                    ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                    ->columns(2)
                ])
                ->columns(2),

                // Seaction 5 block
    Builder\Block::make('services')
    ->label('Services Section')
        ->icon('heroicon-m-bars-3-bottom-left')
        ->schema([
            TextInput::make('title.en')
                ->label('Title')
                ->required(),
            TextInput::make('title.ar')
                ->label('العنوان')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),
                Textarea::make('text.en')
                ->autosize()
                ->label('Paragraph')
                ->required(),
                Textarea::make('text.ar')
                ->autosize()
                    ->label('الفقرة')
                    ->extraFieldWrapperAttributes(['class' => 'text-right'])
                    ->required(),
            Repeater::make('services')
            ->columnSpanFull()
                ->label('Services')
                ->schema([
                    TextInput::make('title.en')
                        ->label('Service Title')
                        ->required(),
                    TextInput::make('title.ar')
                        ->label('عنوان الخدمة')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->required(),
                        TagsInput::make('tags.en')
                        ->label('Tags')
                        ->required(),
                    TagsInput::make('tags.ar')
                        ->label('الوسوم')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->required(),
                    TextInput::make('description.en')
                        ->label('Service Description')
                        ->required(),
                    TextInput::make('description.ar')
                        ->label('وصف الخدمة')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->required(),
                        Group::make()
    ->schema([
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
            ])->columns(1),

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
                    ->url(),
            ])->columns(1),
    ])
    ->columns(1),
                    FileUpload::make('icon')
                        ->label('Service Icon')
                        ->directory('uploads/pages/services')
                        ->image()
                        ->imagePreviewHeight('80')
                        ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                ])
                ->collapsible()
                ->cloneable()
                ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                ->columns(2)
                ->minItems(1)
        ]) ->columns(2),

    // Seaction 5 block
    Builder\Block::make('partners')
    ->label('Partners Section')
        ->icon('heroicon-m-bars-3-bottom-left')
        ->schema([
            TextInput::make('title.en')
                ->label('Title')
                ->required(),
            TextInput::make('title.ar')
                ->label('العنوان')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required(),
                Repeater::make('logos')
                ->columnSpanFull()
                ->label('Logos')
                ->schema([
                    FileUpload::make('image')
                        ->label('Image')
                        ->directory('uploads/pages')
                        ->image()
                        ->imagePreviewHeight('100')
                        ->required()
                        ->imageEditor()
                        ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
            
                    TextInput::make('title.en')
                        ->label('Title')
                        ->required(),
                    TextInput::make('title.ar')
                        ->label('العنوان')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->required(),
                ])
                ->columns(3)
                ->columnSpanFull()
                ->createItemButtonLabel('Add Logo')
                ->cloneable()
                ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                ->required(),
        ]) ->columns(2),
])
->collapsible()
->cloneable()
->deleteAction(fn (Action $action) => $action->requiresConfirmation())

    ,
            
        ])
            ->statePath('data');
    }

    public function save(): void
    {
        if (! auth()->user()?->can('update LandingPage')) {
            Notification::make()
                ->title('You do not have permission to edit this page.')
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        // Validate that registration number and URL are provided when banner is enabled
        if (!empty($data['government_verification_banner_enabled'])) {
            if (empty($data['dga_registration_number']) || empty($data['dga_certificate_url'])) {
                Notification::make()
                    ->title('Validation Error')
                    ->body('DGA Registration Number and Certificate URL are required when the banner is enabled.')
                    ->danger()
                    ->send();
                return;
            }
        }

        $landingPage = LandingPageModel::first() ?? new LandingPageModel();
        $landingPage->fill($data);
        $landingPage->save();

        Notification::make()
            ->title('Saved Successfully')
            ->success()
            ->send();
    }


    public function get(): array
        {
            $page = LandingPageModel::first();     
          $content = $page->content;

            if (!$content) return [];

            array_walk_recursive($content, function (&$item) {
                if (is_string($item) && str_starts_with($item, 'uploads/')) {
                    $item = asset('storage/' . $item);
                }
            });
            return $content;

           // return $this->form->getState();
    }
    public function show(Request $request)
    {
        $page = LandingPageModel::first(); 
        return new LandingPageResource($page);
    }
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view LandingPage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update LandingPage');
    }
    
}
