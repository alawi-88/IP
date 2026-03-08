<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiProviderResource\Pages;
use App\Models\AiProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProviderResource extends Resource
{
    protected static ?string $model = AiProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'AI Providers';

    protected static ?string $modelLabel = 'AI Provider';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Provider Setup')
                    ->description('Configure the AI provider connection details.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Anthropic Claude Sonnet'),
                        Forms\Components\Select::make('provider_type')
                            ->required()
                            ->options([
                                'anthropic' => 'Anthropic (Claude)',
                                'openai' => 'OpenAI (GPT)',
                                'gemini' => 'Google (Gemini)',
                            ])
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                // Auto-fill defaults based on provider type
                                $defaults = match ($state) {
                                    'anthropic' => [
                                        'base_url' => 'https://api.anthropic.com/v1',
                                        'default_model' => 'claude-sonnet-4-20250514',
                                    ],
                                    'openai' => [
                                        'base_url' => 'https://api.openai.com/v1',
                                        'default_model' => 'gpt-4o',
                                    ],
                                    'gemini' => [
                                        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                                        'default_model' => 'gemini-2.0-flash',
                                    ],
                                    default => [],
                                };

                                foreach ($defaults as $key => $value) {
                                    $set($key, $value);
                                }
                            }),
                        Forms\Components\TextInput::make('api_key')
                            ->required()
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->placeholder('Enter API key or token'),
                        Forms\Components\TextInput::make('base_url')
                            ->label('Base URL')
                            ->url()
                            ->placeholder('Auto-filled based on provider type'),
                        Forms\Components\TextInput::make('max_tokens')
                            ->numeric()
                            ->default(4096)
                            ->minValue(256)
                            ->maxValue(128000),
                    ])->columns(2),

                Forms\Components\Section::make('Model Configuration')
                    ->description('Configure available models and their costs.')
                    ->schema([
                        Forms\Components\TextInput::make('default_model')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. claude-sonnet-4-20250514'),
                        Forms\Components\Repeater::make('models')
                            ->label('Available Models')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder('Model name'),
                                Forms\Components\TextInput::make('cost_per_1k_input')
                                    ->label('Cost/1K Input Tokens ($)')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->placeholder('0.003'),
                                Forms\Components\TextInput::make('cost_per_1k_output')
                                    ->label('Cost/1K Output Tokens ($)')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->placeholder('0.015'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Model')
                            ->collapsible()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Priority & Cost')
                    ->description('Lower priority number = tried first. Cost is used for automatic provider selection.')
                    ->schema([
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Lower = higher priority (tried first)'),
                        Forms\Components\TextInput::make('cost_per_1k_tokens')
                            ->label('Simplified Cost / 1K Tokens ($)')
                            ->numeric()
                            ->step(0.000001)
                            ->default(0)
                            ->helperText('Used for provider cost comparison and auto-selection'),
                    ])->columns(2),

                Forms\Components\Section::make('Budget & Monthly Limits')
                    ->description('Set spending and token limits per billing period (30 days).')
                    ->schema([
                        Forms\Components\TextInput::make('monthly_budget')
                            ->label('Monthly Budget (USD)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->placeholder('e.g. 50.00')
                            ->helperText('Leave blank for unlimited spending'),
                        Forms\Components\TextInput::make('monthly_tokens_limit')
                            ->label('Monthly Token Limit')
                            ->numeric()
                            ->minValue(1000)
                            ->placeholder('e.g. 1000000')
                            ->helperText('Leave blank for unlimited tokens'),
                    ])->columns(2),

                Forms\Components\Section::make('Reliability Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive providers are skipped during generation'),
                        Forms\Components\TextInput::make('auto_disable_threshold')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Auto-disable after this many consecutive errors'),
                        Forms\Components\TextInput::make('max_retries')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(10),
                    ])->columns(3),

                Forms\Components\Section::make('Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('error_count')
                            ->label('Consecutive Errors')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_requests')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_tokens_used')
                            ->disabled(),
                        Forms\Components\TextInput::make('monthly_spend')
                            ->label('Monthly Spend ($)')
                            ->disabled(),
                        Forms\Components\TextInput::make('monthly_tokens_used')
                            ->label('Monthly Tokens Used')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('budget_reset_at')
                            ->label('Budget Period Started')
                            ->disabled(),
                        Forms\Components\Textarea::make('last_error')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('last_error_at')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('last_used_at')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('provider_type')
                    ->label('Provider')
                    ->colors([
                        'primary' => 'anthropic',
                        'success' => 'openai',
                        'warning' => 'gemini',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'anthropic' => 'Anthropic',
                        'openai' => 'OpenAI',
                        'gemini' => 'Gemini',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('default_model')
                    ->label('Model')
                    ->limit(30),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state) => $state <= 5 ? 'success' : ($state <= 15 ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('cost_per_1k_tokens')
                    ->label('Cost/1K')
                    ->sortable()
                    ->money('usd', divideBy: 1)
                    ->formatStateUsing(fn ($state) => '$' . number_format((float) $state, 4)),
                Tables\Columns\ViewColumn::make('monthly_budget')
                    ->label('Budget')
                    ->view('filament.custom-columns.budget-progress'),
                Tables\Columns\ViewColumn::make('monthly_tokens_limit')
                    ->label('Token Limit')
                    ->view('filament.custom-columns.token-progress'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('error_count')
                    ->label('Errors')
                    ->badge()
                    ->color(fn (int $state) => $state === 0 ? 'success' : ($state < 3 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('total_requests')
                    ->label('Requests')
                    ->formatStateUsing(fn ($state) => number_format($state)),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('priority', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('provider_type')
                    ->options([
                        'anthropic' => 'Anthropic',
                        'openai' => 'OpenAI',
                        'gemini' => 'Gemini',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resetErrors')
                    ->label('Reset Errors')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (AiProvider $record) {
                        $record->resetErrors();
                        Notification::make()
                            ->title('Errors reset successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (AiProvider $record) => $record->error_count > 0),
                Tables\Actions\Action::make('testConnection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (AiProvider $record) {
                        try {
                            $result = static::testProviderConnection($record);
                            Notification::make()
                                ->title('Connection successful!')
                                ->body("Provider responded in {$result['latency_ms']}ms")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Connection failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Test a provider's API connection with a minimal request.
     */
    protected static function testProviderConnection(AiProvider $provider): array
    {
        $start = microtime(true);

        try {
            $response = match ($provider->provider_type) {
                'anthropic' => Http::withHeaders([
                    'x-api-key' => $provider->api_key,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->timeout(15)->post(($provider->base_url ?? 'https://api.anthropic.com/v1') . '/messages', [
                    'model' => $provider->default_model,
                    'max_tokens' => 10,
                    'messages' => [['role' => 'user', 'content' => 'Say "ok"']],
                ]),

                'openai' => Http::withHeaders([
                    'Authorization' => "Bearer {$provider->api_key}",
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post(($provider->base_url ?? 'https://api.openai.com/v1') . '/chat/completions', [
                    'model' => $provider->default_model,
                    'max_tokens' => 10,
                    'messages' => [['role' => 'user', 'content' => 'Say "ok"']],
                ]),

                'gemini' => Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post(
                    ($provider->base_url ?? 'https://generativelanguage.googleapis.com/v1beta')
                    . "/models/{$provider->default_model}:generateContent?key={$provider->api_key}",
                    [
                        'contents' => [['parts' => [['text' => 'Say "ok"']]]],
                        'generationConfig' => ['maxOutputTokens' => 10],
                    ]
                ),

                default => throw new \RuntimeException("Unknown provider type: {$provider->provider_type}"),
            };

            $latency = round((microtime(true) - $start) * 1000);

            if (!$response->successful()) {
                throw new \RuntimeException("API returned HTTP {$response->status()}: " . substr($response->body(), 0, 200));
            }

            return ['latency_ms' => $latency];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Connection failed: ' . $e->getMessage());
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiProviders::route('/'),
            'create' => Pages\CreateAiProvider::route('/create'),
            'edit' => Pages\EditAiProvider::route('/{record}/edit'),
        ];
    }
}
