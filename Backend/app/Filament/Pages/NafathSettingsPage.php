<?php

namespace App\Filament\Pages;

use App\Models\NafathSettings;
use App\Services\NafathValidationService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class NafathSettingsPage extends Page implements HasActions
{
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.pages.nafath-settings';
    protected static ?string $title = 'Nafath SSO Settings';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?int $navigationSort = 95;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = NafathSettings::current();
        $this->form->fill([
            'is_enabled' => $settings->is_enabled,
            'client_id' => $settings->client_id,
            'client_secret' => $settings->client_secret,
            'redirect_uri' => $settings->redirect_uri,
            'logout_uri' => $settings->logout_uri,
            'environment' => $settings->environment,
            'login_method' => $settings->login_method ?? 'both',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Nafath SSO Configuration')
                    ->description('Configure Nafath Single Sign-On integration for user authentication')
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Enable Nafath SSO')
                            ->helperText('Enable or disable Nafath SSO integration')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('client_id', '');
                                    $set('client_secret', '');
                                    $set('redirect_uri', '');
                                    $set('logout_uri', '');
                                } else {
                                    // Set default values when enabling
                                    $set('redirect_uri', config('app.url') . '/api/nafath/callback');
                                    $set('logout_uri', config('app.url') . '/logout');
                                }
                            }),

                        Forms\Components\Select::make('environment')
                            ->label('Environment')
                            ->options([
                                'staging' => 'Staging (stg-iam.logisti.sa)',
                                'production' => 'Production (iam.logisti.sa)',
                            ])
                            ->default('production')
                            ->required()
                            ->visible(fn (callable $get) => $get('is_enabled')),

                        Forms\Components\Select::make('login_method')
                            ->label('Login Method')
                            ->options(NafathSettings::getLoginMethods())
                            ->default('both')
                            ->required()
                            ->visible(fn (callable $get) => $get('is_enabled'))
                            ->helperText('Select which login methods users can use')
                            ->reactive(),

                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID')
                            ->placeholder('Enter Client ID')
                            ->required(fn (callable $get) => $get('is_enabled'))
                            ->visible(fn (callable $get) => $get('is_enabled'))
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if ($get('is_enabled') && empty($value)) {
                                            $fail('The Client ID is required when Nafath SSO is enabled.');
                                        }
                                    };
                                }
                            ]),

                        Forms\Components\TextInput::make('client_secret')
                            ->label('Client Secret')
                            ->placeholder('Enter Client Secret')
                            ->required(fn (callable $get) => $get('is_enabled'))
                            ->visible(fn (callable $get) => $get('is_enabled'))
                            ->password()
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if ($get('is_enabled') && empty($value)) {
                                            $fail('The Client Secret is required when Nafath SSO is enabled.');
                                        }
                                    };
                                }
                            ]),


                        Forms\Components\TextInput::make('redirect_uri')
                            ->label('Redirect URI')
                            ->placeholder('https://yourdomain.com/api/nafath/callback')
                            ->url()
                            ->required(fn (callable $get) => $get('is_enabled'))
                            ->visible(fn (callable $get) => $get('is_enabled'))
                            ->disabled()
                            ->helperText('The URL where MIP will redirect after authentication'),

                        Forms\Components\TextInput::make('logout_uri')
                            ->label('Logout URI')
                            ->placeholder('https://yourdomain.com/logout')
                            ->url()
                            ->visible(fn (callable $get) => $get('is_enabled'))
                            ->helperText('The URL where users will be redirected after logout'),

                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }


    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // If enabling, validate credentials first
            if ($data['is_enabled'] && !empty($data['client_id']) && !empty($data['client_secret'])) {
                $validationService = new NafathValidationService();
                $validation = $validationService->validateCredentials(
                    $data['client_id'],
                    $data['client_secret'],
                    $data['environment'] ?? 'production'
                );

                if (!$validation['valid']) {
                    Notification::make()
                        ->title('Validation Failed')
                        ->body($validation['message'])
                        ->danger()
                        ->send();
                    return;
                }
            }

            $settings = NafathSettings::current();

            if ($data['is_enabled']) {
                $settings->enable(
                    $data['client_id'],
                    $data['client_secret'],
                    $data['redirect_uri'] ?? config('app.url') . '/api/nafath/callback',
                    $data['logout_uri'] ?? config('app.url') . '/logout',
                    $data['environment'] ?? 'production',
                    $data['login_method'] ?? 'both'
                );
            } else {
                $settings->disable();
            }

            Notification::make()
                ->title('Settings saved successfully!')
                ->success()
                ->send();

            // Refresh the form with updated data
            $this->mount();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An error occurred while saving settings: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testConnection(): void
    {
        $data = $this->form->getState();

        if (!$data['is_enabled'] || empty($data['client_id']) || empty($data['client_secret'])) {
            Notification::make()
                ->title('Invalid Configuration')
                ->body('Please enable Nafath SSO and provide valid credentials before testing.')
                ->warning()
                ->send();
            return;
        }

        $validationService = new NafathValidationService();

        // First test basic connectivity
        $connectivityResult = $validationService->testConnectivity($data['environment'] ?? 'production');

        if (!$connectivityResult['connected']) {
            Notification::make()
                ->title('Connection Failed')
                ->body($connectivityResult['message'])
                ->danger()
                ->send();
            return;
        }

        // Then test credentials
        $validation = $validationService->validateCredentials(
            $data['client_id'],
            $data['client_secret'],
            $data['environment'] ?? 'production'
        );

        if ($validation['valid']) {
            $message = $validation['message'];
            if (isset($validation['note'])) {
                $message .= "\n\nNote: " . $validation['note'];
            }

            Notification::make()
                ->title('Connection Successful')
                ->body($message)
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Connection Failed')
                ->body($validation['message'])
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }


    public static function canAccess(): bool
    {
        return auth()->user()?->can('configure Integrations');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('configure Integrations');
    }
}
