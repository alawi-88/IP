<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Form;
use App\Models\BrandingSetting;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleFontsService;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Str;

class BrandingSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'filament.pages.branding-settings';
    protected static ?string $title = 'Branding Settings';
    protected static ?string $navigationGroup = 'Brandings';
    protected static ?int $navigationSort = 91;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            BrandingSetting::first()?->toArray() ?? []
        );
    }

    public function form(Form $form): Form
{

    return $form
    ->schema([
        //logo
            Forms\Components\FileUpload::make('logo')
                ->label('Logo')
                ->directory('branding')
                ->image()
                ->imagePreviewHeight('100')
                ->maxFiles(1)
                ->required()
                ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),

        //white logo
            Forms\Components\FileUpload::make('white_logo')
            ->label('White Logo')
            ->directory('branding')
            ->image()
            ->imagePreviewHeight('100')
            ->maxFiles(1)
            ->required()
            ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),


        //favicon
            Forms\Components\FileUpload::make('favicon')
            ->label('Favicon')
            ->directory('branding')
            ->image()
            ->imagePreviewHeight('50')
            ->maxFiles(1)
            ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),

        //primary color
            Forms\Components\ColorPicker::make('primary_color')
                ->label('Primary Color')
                ->required()
                ->rules(['regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'])
                ->validationMessages([
                    'required' => 'Primary color is required.',
                    'regex' => 'Primary color must be a valid hex color code (e.g., #FF0000 or #F00).',
                ])
                ->default('#6E62E5'),

        //secondary color
            Forms\Components\ColorPicker::make('secondary_color')
                ->label('Secondary Color')
                ->required()
                ->rules(['regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'])
                ->validationMessages([
                    'required' => 'Secondary color is required.',
                    'regex' => 'Secondary color must be a valid hex color code (e.g., #FF0000 or #F00).',
                ])
                ->default('#4B5563'),

        //font
            Forms\Components\Select::make('font')
                ->label('Font')
                ->options(function () {
                    $fonts = GoogleFontsService::getFonts();
                    // Add Madani Arabic and Mestika fonts to the available options
                    $fonts = array_merge([
                        Str::snake('Madani Arabic', '_') => 'Madani Arabic',
                        Str::snake('Mestika', '_') => 'Mestika'
                    ], $fonts);
                    return $fonts;
                })
                ->searchable()
                ->preload(false),

        //email bg color
            Forms\Components\ColorPicker::make('email_bg_color')
                ->label('Email BG Color')
                ->required()
                ->rules(['regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/']),

        //email text color
            Forms\Components\ColorPicker::make('email_text_color')
                ->label('Email Text Color')
                ->required()
                ->rules(['regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/']),

        //email link color
            Forms\Components\ColorPicker::make('email_link_color')
                ->label('Header Background Color')
                ->required()
                ->rules(['regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/']),

            
        //email font size
            Forms\Components\TextInput::make('email_border_color')
                ->label('Email Font Size')
                ->numeric()
                ->required()
                ->numeric()
                ->suffix('px')
                ->default('20'),

        

        //email logo
            Forms\Components\FileUpload::make('email_logo')
                ->label('Email Header Logo')
                ->directory('branding')
                ->image()
                ->imagePreviewHeight('100')
                ->maxFiles(1)
                ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),

        //email footer footer
            Forms\Components\FileUpload::make('email_footer_footer')
                ->label('Email Footer Logo')
                ->directory('branding')
                ->image()
                ->imagePreviewHeight('100')
                ->maxFiles(1)
                ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
        //email footer
        Forms\Components\TextInput::make('email_footer')
        ->label('Copyright Footer')
        ->required()
        ->helperText('The footer of the email will be displayed at the bottom of the email.')
        ->columnSpanFull(),
    ])
        ->statePath('data');
}

    protected function getFormActions(): array
    {
        $canEdit = auth()->user()?->can('update BrandingSettings');

        return $canEdit
            ? [
                Action::make('save')
                    ->label(__('Save'))
                    ->submit('save'),
            ]
            : [];
    }

public function save(): void
{
    $setting = BrandingSetting::first() ?? new BrandingSetting();
    $setting->fill($this->form->getState());
    $setting->save();

    Notification::make()
        ->title('Settings saved successfully!')
        ->success()
        ->send();

    $this->redirect(static::getUrl());
}

public function get()
{
    $app = str(config('app.name'))->lower();
    $app = trim(str_replace( 'system', '', $app));

    $branding = BrandingSetting::first();
    return [
        'logo' => $branding->logo ? Storage::url($branding->logo) : url('media/' . $app . '-light-logo.png'),
        'white_logo' => $branding->white_logo ? Storage::url($branding->white_logo) : url('media/' . $app . '-dark-logo.png'),
        'favicon' => $branding->favicon ? Storage::url($branding->favicon) : url('media/' . $app . '-favicon.ico'),
        'primary_color' => $branding->primary_color,
        'secondary_color' => $branding->secondary_color,
        'font' => $branding->font,
        'email_bg_color' => $branding->email_bg_color,
        'email_text_color' => $branding->email_text_color,
        'email_link_color' => $branding->email_link_color,
        'email_font_size' => $branding->email_border_color . 'px',
        'email_footer' => $branding->email_footer,
        'email_logo' => $branding->email_logo ? Storage::url($branding->email_logo) : '',
        'email_footer_footer' => $branding->email_footer_footer ? Storage::url($branding->email_footer_footer) : '',
    ];
}


    public static function canAccess(): bool
    {
        return auth()->user()?->can('view BrandingSettings') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update BrandingSettings');
    }


}
