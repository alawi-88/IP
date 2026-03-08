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

class BrandingSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'Branding Settings';
    protected static ?string $title = 'Branding Settings';
    protected static ?string $slug = 'branding-settings';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationGroup = 'Settings';

    protected static string $view = 'filament.pages.branding-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $branding = BrandingSetting::first();
        if ($branding) {
            $this->form->fill([
                'logo' => $branding->logo ? [$branding->logo] : [],
                'white_logo' => $branding->white_logo ? [$branding->white_logo] : [],
                'favicon' => $branding->favicon ? [$branding->favicon] : [],
                'primary_color' => $branding->primary_color ?? '#25935F',
                'secondary_color' => $branding->secondary_color ?? '#1a6b44',
                'font' => $branding->font ?? 'IBM Plex Sans',
                'email_bg_color' => $branding->email_bg_color ?? '#FFFFFF',
                'email_text_color' => $branding->email_text_color ?? '#111827',
                'email_link_color' => $branding->email_link_color ?? '#1E40AF',
                'email_border_color' => $branding->email_border_color ?? '#E5E7EB',
                'email_footer' => $branding->email_footer ?? '',
                'email_logo' => $branding->email_logo ? [$branding->email_logo] : [],
                'email_footer_footer' => $branding->email_footer_footer ? [$branding->email_footer_footer] : [],
            ]);
        } else {
            $this->form->fill([
                'primary_color' => '#25935F',
                'secondary_color' => '#1a6b44',
                'font' => 'IBM Plex Sans',
                'email_bg_color' => '#FFFFFF',
                'email_text_color' => '#111827',
                'email_link_color' => '#1E40AF',
                'email_border_color' => '#E5E7EB',
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Branding')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Brand Identity')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Section::make('Logos')
                                    ->description('Upload your brand logos in SVG or PNG format')
                                    ->schema([
                                        Forms\Components\FileUpload::make('logo')
                                            ->label('Primary Logo')
                                            ->directory('branding')
                                            ->image()
                                            ->imagePreviewHeight('100')
                                            ->maxFiles(1)
                                            ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg'])
                                            ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                                        Forms\Components\FileUpload::make('white_logo')
                                            ->label('White Logo (for dark backgrounds)')
                                            ->directory('branding')
                                            ->image()
                                            ->imagePreviewHeight('100')
                                            ->maxFiles(1)
                                            ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg'])
                                            ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                                        Forms\Components\FileUpload::make('favicon')
                                            ->label('Favicon')
                                            ->directory('branding')
                                            ->image()
                                            ->imagePreviewHeight('50')
                                            ->maxFiles(1)
                                            ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                                            ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                                    ])->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Colors & Typography')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Forms\Components\Section::make('Brand Colors')
                                    ->description('These colors will be applied across the entire platform')
                                    ->schema([
                                        Forms\Components\ColorPicker::make('primary_color')
                                            ->label('Primary Color')
                                            ->helperText('Main brand color used for buttons, links, and accents')
                                            ->required(),
                                        Forms\Components\ColorPicker::make('secondary_color')
                                            ->label('Secondary Color')
                                            ->helperText('Used for hover states and secondary elements')
                                            ->required(),
                                    ])->columns(2),

                                Forms\Components\Section::make('Typography')
                                    ->description('Select the font family for the platform')
                                    ->schema([
                                        Forms\Components\Select::make('font')
                                            ->label('Font Family')
                                            ->options([
                                                'IBM Plex Sans' => 'IBM Plex Sans (DGA Standard)',
                                                'Inter' => 'Inter',
                                                'Cairo' => 'Cairo (Arabic optimized)',
                                                'Tajawal' => 'Tajawal (Arabic optimized)',
                                                'Noto Sans Arabic' => 'Noto Sans Arabic',
                                                'Roboto' => 'Roboto',
                                                'Open Sans' => 'Open Sans',
                                                'Poppins' => 'Poppins',
                                                'Nunito' => 'Nunito',
                                            ])
                                            ->required()
                                            ->helperText('Font used throughout the platform'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Email Settings')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make('Email Branding')
                                    ->description('Customize the appearance of emails sent from the platform')
                                    ->schema([
                                        Forms\Components\FileUpload::make('email_logo')
                                            ->label('Email Logo')
                                            ->directory('branding')
                                            ->image()
                                            ->imagePreviewHeight('80')
                                            ->maxFiles(1)
                                            ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                                        Forms\Components\FileUpload::make('email_footer_footer')
                                            ->label('Email Footer Image')
                                            ->directory('branding')
                                            ->image()
                                            ->imagePreviewHeight('80')
                                            ->maxFiles(1)
                                            ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),
                                    ])->columns(2),

                                Forms\Components\Section::make('Email Colors')
                                    ->schema([
                                        Forms\Components\ColorPicker::make('email_bg_color')
                                            ->label('Background Color'),
                                        Forms\Components\ColorPicker::make('email_text_color')
                                            ->label('Text Color'),
                                        Forms\Components\ColorPicker::make('email_link_color')
                                            ->label('Link Color'),
                                        Forms\Components\ColorPicker::make('email_border_color')
                                            ->label('Border Color'),
                                    ])->columns(4),

                                Forms\Components\Section::make('Email Footer')
                                    ->schema([
                                        Forms\Components\Textarea::make('email_footer')
                                            ->label('Footer Text')
                                            ->rows(3)
                                            ->helperText('Copyright notice and other footer text for emails'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Branding Settings')
                ->submit('save')
                ->icon('heroicon-o-check'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Handle file uploads - extract first file from array
        $fileFields = ['logo', 'white_logo', 'favicon', 'email_logo', 'email_footer_footer'];
        foreach ($fileFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = !empty($data[$field]) ? reset($data[$field]) : null;
            }
        }

        $branding = BrandingSetting::first();
        if ($branding) {
            $branding->update($data);
        } else {
            BrandingSetting::create($data);
        }

        Notification::make()
            ->title('Branding settings saved successfully!')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        // Allow all authenticated admin users to access branding settings
        return true;
    }

    public static function getApiData(): array
    {
        $branding = BrandingSetting::first();
        if (!$branding) {
            return [
                'primary_color' => '#25935F',
                'secondary_color' => '#1a6b44',
                'font' => 'IBM Plex Sans',
                'logo' => null,
                'white_logo' => null,
                'favicon' => null,
            ];
        }

        return [
            'primary_color' => $branding->primary_color ?? '#25935F',
            'secondary_color' => $branding->secondary_color ?? '#1a6b44',
            'font' => $branding->font ?? 'IBM Plex Sans',
            'logo' => $branding->logo ? Storage::url($branding->logo) : null,
            'white_logo' => $branding->white_logo ? Storage::url($branding->white_logo) : null,
            'favicon' => $branding->favicon ? Storage::url($branding->favicon) : null,
            'email_bg_color' => $branding->email_bg_color ?? '#FFFFFF',
            'email_text_color' => $branding->email_text_color ?? '#111827',
            'email_link_color' => $branding->email_link_color ?? '#1E40AF',
            'email_border_color' => $branding->email_border_color ?? '#E5E7EB',
            'email_footer' => $branding->email_footer ?? '',
            'email_logo' => $branding->email_logo ? Storage::url($branding->email_logo) : null,
            'email_footer_footer' => $branding->email_footer_footer ? Storage::url($branding->email_footer_footer) : null,
        ];
    }
}

