<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationMessageResource\Pages;
use App\Filament\Resources\NotificationMessageResource\RelationManagers;
use App\Filament\Traits\CanBeDeletable;
use App\Models\NotificationMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\ViewAction;
class NotificationMessageResource extends Resource
{
    use CanBeDeletable;
    
    protected static ?string $model = NotificationMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Notifications & Approvals';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
        Forms\Components\Select::make('key')
            ->label('Template Type')
            ->options([
                
                'user.application_status_updates'   => 'User - Application Status Updates',
                'user.project_status_updates'       => 'User - Project Status Updates',
                'user.project_submitted'            => 'User - Project Submitted',
                //'user.winners_announcement'            => 'User - Winners Announcement',
                'user.team_addition'               => 'User - Team Updates',
                'user.project_comment_added'       => 'User - Communication Channel Updates',
                'user.application_comment_added'   => 'User - Application Comment Added',
                'user.participant_application_reply'   => 'User - Participant Application Reply',

               // 'judge.assigned_to_evaluate'        => 'Judge - Assigned to Evaluate',
                //'judge.evaluation_submission_confirmation'            => 'Judge - Evaluation Submission Confirmation',
            ])
            ->required()
            ->searchable()
            ->preload()
            ->reactive()
           // ->disabled(fn () => ! auth()->user()->can('update NotificationMessage'))
                ->unique(ignoreRecord: true),
            Forms\Components\Tabs::make('Languages')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('English')
                        ->schema([
                            Forms\Components\TextInput::make('subject.en')
                                ->label('Subject')
                               // ->disabled(fn () => ! auth()->user()->can('update NotificationMessage'))
                                ->required(),

                            Forms\Components\Textarea::make('body.en')
                                ->label('Body')
                                ->maxLength(200)
                                ->validationMessages([
                                    'max' => 'Content must not exceed 200 characters.',
                                ])
                                ->dehydrateStateUsing(fn ($state) => strip_tags(preg_replace('/\s+/', ' ', $state)))
                                ->rules(['regex:/^[^<>]+$/'])
                                //->disabled(fn () => ! auth()->user()->can('update NotificationMessage'))
                                ->required()
                                ->helperText(fn ($get) => self::getHelperText($get('key'), 'en'))
                                ->rules([
                                    function ($get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $helperText = self::getHelperText($get('key'), 'en');
                                            
                                            // استخرج placeholders المسموحة من الـ helperText
                                            preg_match_all('/\{\{(.*?)\}\}/', $helperText, $matches);
                                            $allowedPlaceholders = $matches[0]; // مع الأقواس {{...}}
                                            
                                            // استخرج كل placeholders من body
                                            preg_match_all('/\{\{(.*?)\}\}/', $value, $bodyMatches);
                                            $bodyPlaceholders = $bodyMatches[0];
                                            
                                            // تحقق إن كل placeholder في body مسموح
                                            foreach ($bodyPlaceholders as $ph) {
                                                if (! in_array($ph, $allowedPlaceholders)) {
                                                    $fail("The placeholder {$ph} is not allowed for this template.");
                                                }
                                            }
                                            
                                            // تحقق إن كل placeholder المطلوب موجود
                                            foreach ($allowedPlaceholders as $ph) {
                                                if (! str_contains($value, $ph)) {
                                                    $fail("The body must contain the placeholder: {$ph}");
                                                }
                                            }
                                        };
                                    },
                                ])
                                
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('العربية')
                        ->schema([
                            Forms\Components\TextInput::make('subject.ar')
                                ->label('الموضوع')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                //->disabled(fn () => ! auth()->user()->can('update NotificationMessage'))
                                ->required(),

                            Forms\Components\Textarea::make('body.ar')
                                ->label('المحتوى')
                                ->maxLength(200)
                                ->validationMessages([
                                    'max' => 'يجب ألا يزيد المحتوى عن 200 حرفًا.',
                                ])
                                //->validationAttribute('المحتوى')
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->dehydrateStateUsing(fn ($state) => strip_tags(preg_replace('/\s+/', ' ', $state)))
                                ->rules(['regex:/^[^<>]+$/'])
                                //->disabled(fn () => ! auth()->user()->can('update NotificationMessage'))
                                ->required()
                                ->helperText(fn ($get) => self::getHelperText($get('key'), 'ar'))
                                ->rules([
                                    function ($get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $helperText = self::getHelperText($get('key'), 'ar');
                                            
                                            // استخرج placeholders المسموحة من الـ helperText
                                            preg_match_all('/\{\{(.*?)\}\}/', $helperText, $matches);
                                            $allowedPlaceholders = $matches[0]; // مع الأقواس {{...}}
                                            
                                            // استخرج كل placeholders من body
                                            preg_match_all('/\{\{(.*?)\}\}/', $value, $bodyMatches);
                                            $bodyPlaceholders = $bodyMatches[0];
                                            
                                            // تحقق إن كل placeholder في body مسموح
                                            foreach ($bodyPlaceholders as $ph) {
                                                if (! in_array($ph, $allowedPlaceholders)) {
                                                    $fail("The placeholder {$ph} is not allowed for this template.");
                                                }
                                            }
                                            
                                            // تحقق إن كل placeholder المطلوب موجود
                                            foreach ($allowedPlaceholders as $ph) {
                                                if (! str_contains($value, $ph)) {
                                                    $fail("The body must contain the placeholder: {$ph}");
                                                }
                                            }
                                        };
                                    },
                                ])
                                
                               
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
                Forms\Components\Toggle::make('is_default')
            ->onColor('success')
            ->offColor('danger')
            ->default(false)
            ->label('Activate Default Template')
            ->helperText(fn () => auth()->user()->can('update NotificationMessage') ? 'You can activate the default template' : 'You are not authorized to activate the default template')
            //->disabled(fn () => ! auth()->user()->can('update NotificationMessage'))
            ,
            
           

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key'),
                Tables\Columns\TextColumn::make('subject.en'),
                Tables\Columns\TextColumn::make('subject.ar')->label('الموضوع'),
                Tables\Columns\TextColumn::make('body.en'),
                Tables\Columns\TextColumn::make('body.ar')->label('المحتوى'),
                //Tables\Columns\TextColumn::make('type'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()->can('update NotificationMessage')),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete NotificationMessage')),
                Tables\Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view NotificationMessage')),
            ])
            ->bulkActions([
            ]);
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
            'index' => Pages\ListNotificationMessages::route('/'),
            'create' => Pages\CreateNotificationMessage::route('/create'),
            'edit' => Pages\EditNotificationMessage::route('/{record}/edit'),
        ];
    }

    public static function getHelperText(?string $key, string $lang): string
    {
        $placeholders = [
            'user.application_status_updates' => [
                'en' => 'Available variables: {{program}}, {{new_status}}, {{old_status}}',
                'ar' => 'المتغيرات المتاحة: {{program}}, {{new_status}}, {{old_status}}',
            ],
            'user.project_status_updates' => [
                'en' => 'Available variables: {{project}}, {{program}}, {{new_status}}, {{old_status}}',
                'ar' => 'المتغيرات المتاحة: {{project}}, {{program}}, {{new_status}}, {{old_status}}',
            ], 
            'user.winners_announcement' => [
                'en' => 'Available variables: {{code}},{{name}}, {{program}}',
                'ar' => 'المتغيرات المتاحة: {{code}},{{name}}',
            ],
            'user.team_addition' => [
                'en' => 'Available variables: {{team}}, {{program}}',
                'ar' => 'المتغيرات المتاحة: {{team}}, {{program}}',
            ],
            'user.project_comment_added' => [
                'en' => 'Available variables: {{project}}, {{admin}}, {{comment}}',
                'ar' => 'المتغيرات المتاحة: {{project}}, {{admin}}, {{comment}}',
            ],
            'judge.assigned_to_evaluate' => [
                'en' => 'Available variables: {{code}}, {{program}}',
                'ar' => 'المتغيرات المتاحة: {{code}}',
            ], 
            'judge.evaluation_submission_confirmation' => [
                'en' => 'Available variables: {{name}}, {{url}}, {{program}}',
                'ar' => 'المتغيرات المتاحة: {{name}}, {{url}}',
            ],
            'user.project_submitted' => [
                'en' => 'Available variables: {{program}}',
                'ar' => 'المتغيرات المتاحة: {{program}}',
            ],
            'user.application_comment_added' => [
                'en' => 'Available variables: {{program}}, {{admin}}, {{comment}}',
                'ar' => 'المتغيرات المتاحة: {{program}}, {{admin}}, {{comment}}',
            ],
            'user.participant_application_reply' => [
                'en' => 'Available variables: {{program}}, {{name}}, {{comment}}',
                'ar' => 'المتغيرات المتاحة: {{program}}, {{name}}, {{comment}}',
            ],
        ];

        return $key && isset($placeholders[$key][$lang])
    ? $placeholders[$key][$lang]
    : '';   }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create NotificationMessage');
    }
    
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update NotificationMessage');
    }

    public static function canDelete(Model $record): bool 
    {
        return auth()->user()->can('delete NotificationMessage');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view NotificationMessage');
    }
}
