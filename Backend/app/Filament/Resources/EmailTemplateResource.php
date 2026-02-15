<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Filament\Resources\EmailTemplateResource\RelationManagers;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Concerns\CanBeDeletable;
use Filament\Resources\Concerns\CanBeCreated;
use Filament\Resources\Concerns\CanBeEdited;
use Filament\Tables\Actions\Action as TableAction;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    protected static ?string $navigationLabel = 'Email Templates';
    protected static ?string $navigationGroup = 'Notification Management';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?int $navigationSort = 99;
    public static function form(Form $form): Form
    {
        return $form
        ->schema([
        Forms\Components\Select::make('key')
            ->label('Template Type')
            ->options([
                'user.registration_confirmation'   => 'User Emails - Registration Confirmation',
                'user.otp_login'                  => 'User Emails - OTP for Login',     
                'user.competition_confirmation'    => 'User Emails - Application Submitted Successfully',
                'user.screening_result'            => 'User Emails - Application Screening Result',
                'user.application_comment_added'   => 'User Emails - Application Comment Added',
                'user.project_submitted'           => 'User Emails - Project Submitted Successfully',
                'user.project_status_updates'      => 'User Emails - Project Status Updates',
                'user.project_comment_added'       => 'User Emails - Project Comment Added',
                'user.team_addition'               => 'User Emails - Team Addition Confirmation',
                'user.project_evaluation'          => 'User Emails - Project Evaluation Result',
                'user.communication_channel'       => 'User Emails - Communication Channel',
                'user.forgot_password'            => 'User Emails - Forgot Password',

                'judge.signup_confirmation'        => 'Judge Emails - Sign-up Confirmation (Activation)',
                'judge.forgot_password'            => 'Judge Emails - Forgot Password',
                'judge.otp_login'                  => 'Judge Emails - OTP for Login',
                'judge.credentials'                => 'Judge Emails - Credentials Email',
              
                // 'mentor.registration_pending'      => 'Mentor Emails - Registration Pending',
                // 'mentor.registration_confirmation' => 'Mentor Emails - Registration Confirmation',
                // 'mentor.otp_login'                  => 'Mentor Emails - OTP for Login',
                // 'mentor.forgot_password'            => 'Mentor Emails - Forgot Password',
                // 'mentor.credentials'                => 'Mentor Emails - Credentials Email',
                // 'mentor.update_mentor_account'     => 'Mentor Emails - Update Mentor Account',
                // 'mentor.otp_login'                  => 'Mentor Emails - OTP for Login',
                // 'mentor.forgot_password'            => 'Mentor Emails - Forgot Password',
                'mentor.admin_registration_notification' => 'Mentor Emails - Admin Registration Notification',
                'mentor.new_booking_notification' => 'Mentor Emails - New Booking Notification',
                'admin.credentials'                => 'Admin Emails - Credentials Email',
                'admin.update_admin_account'     => 'Admin Emails - Update Admin Account',
                'admin.otp_login'                  => 'Admin Emails - OTP for Login',
                'admin.forgot_password'            => 'Admin Emails - Forgot Password',
                //'admin.application_comment_added'  => 'Admin Emails - Application Comment Added',
                'admin.participant_project_reply'      => 'Admin Emails - Participant Project Reply',
               'admin.participant_application_reply'  => 'Admin Emails - Participant Application Reply',
            ])
            ->required()
            ->searchable()
            ->preload()
            ->reactive()
            ->disabled(fn () => ! auth()->user()->can('update EmailTemplate'))
                ->unique(ignoreRecord: true),
            
            Forms\Components\Tabs::make('Languages')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('English')
                        ->schema([
                            Forms\Components\TextInput::make('subject.en')
                                ->label('Subject')
                                ->disabled(fn () => ! auth()->user()->can('update EmailTemplate'))
                                ->required(),

                            Forms\Components\RichEditor::make('body.en')
                            ->disableToolbarButtons(['codeBlock', 'code'])  
                                ->label('Body')
                                ->disabled(fn () => ! auth()->user()->can('update EmailTemplate'))
                                ->required()
                                ->maxLength(10000)
                                ->helperText(fn ($get) => self::getHelperText($get('key'), 'en'))
                                ->rules([
                                    'max:10000',
                                    function ($get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $helperText = self::getHelperText($get('key'), 'en');
                                            preg_match_all('/\{\{(.*?)\}\}/', $helperText, $matches);
                                
                                
                                            foreach ($matches[0] as $placeholder) {
                                                if (! str_contains($value, $placeholder)) {
                                                    $fail("The body must contain the placeholder: {$placeholder}");
                                                }
                                            }
                                        };
                                    },
                                ])
                                ->validationMessages([
                                    'max' => 'Content must not exceed 10000 characters.',
                                ])
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('العربية')
                        ->schema([
                            Forms\Components\TextInput::make('subject.ar')
                                ->label('الموضوع')
                                ->disabled(fn () => ! auth()->user()->can('update EmailTemplate'))
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->required(),

                            Forms\Components\RichEditor::make('body.ar')
                            ->disableToolbarButtons(['codeBlock', 'code'])
                                ->label('المحتوى')
                                ->disabled(fn () => ! auth()->user()->can('update EmailTemplate'))
                                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                                ->required()
                                ->maxLength(10000)
                                ->helperText(fn ($get) => self::getHelperText($get('key'), 'ar'))
                                ->rules([
                                    'max:10000',
                                    function ($get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $helperText = self::getHelperText($get('key'), 'ar'); // always check English line
                                            preg_match_all('/\{\{(.*?)\}\}/', $helperText, $matches);
                                
                                            foreach ($matches[0] as $placeholder) {
                                                if (! str_contains($value, $placeholder)) {
                                                    $fail("يجب أن يحتوي المحتوى على المتغير: {$placeholder}");
                                                }
                                            }
                                        };
                                    },
                                ])
                                ->validationMessages([
                                    'max' => 'يجب ألا يزيد المحتوى عن 1000 حرفًا.',
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
            ->helperText(fn () => auth()->user()->can('update EmailTemplate') ? 'You can activate the default template' : 'You are not authorized to activate the default template')
            ->disabled(fn () => ! auth()->user()->can('update EmailTemplate')),
            
           

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key'),
                Tables\Columns\TextColumn::make('subject.en')->label('Subject'),
                Tables\Columns\TextColumn::make('subject.ar')->label('الموضوع'),
                Tables\Columns\TextColumn::make('body.en')
                    ->html()
                    ->label('Body')
                    ->limit(100),  

                Tables\Columns\TextColumn::make('body.ar')
                    ->html()
                    ->label('المحتوى')
                    ->limit(100),
                    Tables\Columns\IconColumn::make('is_default')
    ->boolean()
    ->trueIcon('heroicon-o-check-circle')
    ->falseIcon('heroicon-o-x-circle')
    ->label('Default'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('setDefault')
                ->label('Set as Default')
                ->icon('heroicon-o-star')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm Default')
                ->modalSubheading('This will set this template as default. Continue?')
                ->visible(fn ($record) => auth()->user()->can('update EmailTemplate') && ! ($record->is_default ?? false))
                ->action(function (\App\Models\EmailTemplate $record) {
                    $record->update(['is_default' => true]);

                    \Filament\Notifications\Notification::make()
                        ->title('Template set as default')
                        ->success()
                        ->send();
                }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkAction::make('deleteSelected')
                //     ->label('Delete Selected')
                //     ->icon('heroicon-o-trash')
                //     ->color('danger')
                //     ->action(fn($records) => $records->each->delete())
                //     ->requiresConfirmation()
                //     ->modalHeading('Delete selected templates?')
                //     ->modalDescription('This will delete the selected templates. Continue?')
                //     ->modalSubmitActionLabel('Yes, delete them')
                //     ->modalCancelActionLabel('Cancel'),
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
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view EmailTemplate') ?? false;
    }
    public static function canCreate(): bool
    {
       return auth()->user()?->can('create EmailTemplate') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update EmailTemplate');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
    public static function canDeleteAny(): bool
    {
        return false;
    }
    public static function getHelperText(?string $key, string $lang): string
{
    $placeholders = [
        'judge.otp_login' => [
            'en' => 'Available variables: {{otpCode}}',
            'ar' => 'المتغيرات المتاحة: {{otpCode}}',
        ],
        'judge.credentials' => [
            'en' => 'Available variables: {{name}}, {{email}}, {{password}}, {{loginUrl}}',
            'ar' => 'المتغيرات المتاحة: {{name}}, {{email}}, {{password}}, {{loginUrl}}',
        ], 
        'judge.forgot_password' => [
            'en' => 'Available variables: {{code}},{{name}}',
            'ar' => 'المتغيرات المتاحة: {{code}},{{name}}',
        ],
        'judge.signup_confirmation' => [
            'en' => 'Available variables: {{name}}, {{url}}',
            'ar' => 'المتغيرات المتاحة: {{name}}, {{url}}',
        ],
        'admin.credentials' => [
            'en' => 'Available variables: {{name}}, {{email}}, {{role}}, {{password}}, {{loginUrl}}',
            'ar' => 'المتغيرات المتاحة: {{name}}, {{email}}, {{role}}, {{password}}, {{loginUrl}}',
        ],
        'admin.update_admin_account' => [
            'en' => 'Available variables: {{name}}, {{email}}, {{role}}, {{password}}, {{loginUrl}}',
            'ar' => 'المتغيرات المتاحة: {{name}}, {{email}}, {{role}}, {{password}}, {{loginUrl}}',
        ],
        'admin.otp_login' => [
            'en' => 'Available variables: {{otpCode}}',
            'ar' => 'المتغيرات المتاحة: {{otpCode}}',
        ],
        'user.forgot_password' => [
            'en' => 'Available variables: {{name}}, {{code}}',
            'ar' => 'المتغيرات المتاحة: {{name}}, {{code}}',
        ], 
        'admin.forgot_password' => [
            'en' => 'Available variables: {{ResetPasswordLink}}',
            'ar' => 'المتغيرات المتاحة: {{ResetPasswordLink}}',
        ],
        'user.otp_login' => [
            'en' => 'Available variables: {{otpCode}}',
            'ar' => 'المتغيرات المتاحة: {{otpCode}}',
        ],
        'user.registration_confirmation' => [
            'en' => 'Available variables: {{name}}, {{url}}',
            'ar' => 'المتغيرات المتاحة: {{name}}, {{url}}',
        ],
        'user.competition_confirmation' => [
            'en' => 'Available variables: {{competition}}, {{name}}',
            'ar' => 'المتغيرات المتاحة: {{competition}}, {{name}}',
        ],
        'user.project_status_updates' => [
            'en' => 'Available variables: {{project}}, {{competition}}, {{newStatus}}, {{oldStatus}}',
            'ar' => 'المتغيرات المتاحة: {{project}}, {{competition}}, {{newStatus}}, {{oldStatus}}',
        ],
        'user.screening_result' => [
            'en' => 'Available variables: {{competition}}, {{new_status}}, {{old_status}}',
            'ar' => 'المتغيرات المتاحة: {{competition}}, {{new_status}}, {{old_status}}',
        ],
        'user.team_addition' => [
            'en' => 'Available variables: {{team}}, {{competition}}, {{url}}',
            'ar' => 'المتغيرات المتاحة: {{team}}, {{competition}}, {{url}}',
        ],
        'user.project_evaluation' => [
            'en' => 'Available variables: {{name}}, {{appName}}, {{project}}, {{competition}}',
            'ar' => 'المتغيرات المتاحة: {{name}}, {{appName}}, {{project}}, {{competition}}',
        ],
        'user.communication_channel' => [
            'en' => 'Available variables: {{project}}, {{admin}}, {{comment}},{{name}}',
            'ar' => 'المتغيرات المتاحة: {{project}}, {{admin}}, {{comment}},{{name}}',
        ], 
        'admin.application_comment_added' => [
            'en' => 'Available variables: {{competition}}, {{commenterName}}, {{NotifiableName}}, {{comment}}',
            'ar' => 'المتغيرات المتاحة: {{competition}}, {{commenterName}}, {{NotifiableName}}, {{comment}}',
        ],
        'user.application_comment_added' => [
            'en' => 'Available variables: {{competition}}, {{commenterName}}, {{NotifiableName}}, {{comment}}',
            'ar' => 'المتغيرات المتاحة: {{competition}}, {{commenterName}}, {{NotifiableName}}, {{comment}}',
        ],
        'admin.participant_project_reply' => [
            'en' => 'Available variables: {{project}}, {{AdminName}}, {{UserName}}, {{comment}}',
            'ar' => 'المتغيرات المتاحة: {{project}}, {{AdminName}}, {{UserName}}, {{comment}}',
        ],
        'user.project_comment_added' => [
            'en' => 'Available variables: {{project}}, {{AdminName}}, {{UserName}}, {{comment}}',
            'ar' => 'المتغيرات المتاحة: {{project}}, {{AdminName}}, {{UserName}}, {{comment}}',
        ],
        'admin.participant_application_reply' => [
            'en' => 'Available variables: {{competition}}, {{AdminName}}, {{UserName}}, {{comment}}',
            'ar' => 'المتغيرات المتاحة: {{competition}}, {{AdminName}}, {{UserName}}, {{comment}}',
        ],
        'user.project_submitted' => [
            'en' => 'Available variables: {{competition}}, {{name}}',
            'ar' => 'المتغيرات المتاحة: {{competition}}, {{name}}',
        ],
        'mentor.admin_registration_notification' => [
            'en' => 'Available variables: {{name}}, {{email}}, {{phone}}, {{profession}}, {{experience}}, {{date}}',  
            'ar' => 'المتغيرات المتاحة: {{name}}, {{email}}, {{phone}}, {{profession}}, {{experience}}, {{date}}',
        ],
        'mentor.new_booking_notification' => [
            'en' => 'Available variables: {{participant}}, {{program}}, {{description}}, {{date}}, {{time}}, {{duration}}, {{id}}',
            'ar' => 'المتغيرات المتاحة: {{participant}}, {{program}}, {{description}}, {{date}}, {{time}}, {{duration}}, {{id}}',
        ],
    ];

    return $key && isset($placeholders[$key][$lang])
        ? $placeholders[$key][$lang]
        : 'Use {{project}}, {{admin}}, {{comment}},{{url}},{{name}}, {{password}}, {{loginUrl}}, {{role}}, {{code}}, {{competition}}, {{new_status}}, {{old_status}}, {{team}}, {{loginUrl}} placeholders';
}
}
