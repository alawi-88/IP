<?php

namespace App\Models;

use App\Notifications\Participant\ActivationEmail;
use App\Traits\HasActivityLog;
use Carbon\Carbon;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Tables;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static create(array $data)
 * @method static where(string $string, mixed $value)
 * @method static updateOrCreate(string[] $array, string[] $array1)
 */
class Participant extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity, HasActivityLog;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'serial_number',
        'name',
        'email',
        'recovery_email',
        'recovery_email_temp',
        'email_verified_at',
        'activation_code',
        'phone',
        'gender',
        'date_of_birth',
        'nationality_id',
        'country_id',
        'residence_city_id',
        'password',
        'educational_background',
        'current_role',
        'place_of_work_study',
        'years_of_experience',
        'experience_or_skills',
        'key_achievements',
        'password_reset_code',
        'last_login_at',
        'otp_login_code_expires_at',
        'login_by',
        'nafath_data',
        'is_active',
        'is_archived',
        'archived_at'
    ];

    protected array $logFields = [
        'serial_number',
        'name',
        'email',
        'phone',
        'recovery_email',
        'gender',
        'date_of_birth',
        'educational_background',
        'current_role',
        'place_of_work_study',
        'years_of_experience',
        'experience_or_skills',
        'key_achievements',
        'is_active',
        'nationality.name',
        'country.name',
        'residenceCity.name',
        'is_archived',
        'archived_at'
    ];

    protected string $moduleName = 'Participant';
    protected string $logName = 'participant';


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date:Y-m-d',
        'last_login_at' => 'datetime',
        'nafath_data' => 'array',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'otp_login_code_expires_at' => 'datetime',
    ];

    /**
     * Override the default notification
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new ActivationEmail);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Participant $participant) {

            if (!is_numeric($participant->nationality_id)) {
                $participant->nationality_id = Nationality::whereJsonContainsLocale('name', 'en', $participant['nationality_id'])->first()?->id;
            }

            if (!is_numeric($participant->country_id)) {
                $participant->country_id = Country::whereJsonContainsLocale('name', 'en', $participant['country_id'])->first()?->id;
            }

            if (!is_numeric($participant->residence_city_id)) {
                $participant->residence_city_id = City::whereJsonContainsLocale('name', 'en', $participant['residence_city_id'])->first()?->id;
            }

            $participant->serial_number = generateSerialNumber();
            $participant->activation_code = bin2hex(random_bytes(32));
        });
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function residenceCity(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProgramApplication::class)->submission()->active();
    }

    public function team(): HasOneThrough
    {
        return $this->hasOneThrough(
            Team::class,
            TeamMember::class,
            'participant_id',
            'id',
            'id',
            'team_id'
        );
    }

    public function satisfactions(): HasMany
    {
        return $this->hasMany(Satisfaction::class);
    }

    public function contactUsMessages(): HasMany
    {
        return $this->hasMany(ContactUs::class);
    }

    /**
     * Get mentors assigned to this participant (for individual/non-team participants)
     */
    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(Mentor::class, 'mentor_participant')
            ->withPivot(['assigned_by', 'assigned_at', 'notes', 'program_id'])
            ->withTimestamps();
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id'),
            Tables\Columns\TextColumn::make('serial_number')->badge()->searchable(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('nationality.name')
                ->searchable()
                ->sortable()
                ->getStateUsing(function ($record) {
                    // If nationality relationship exists, return it
                    if ($record->nationality) {
                        return $record->nationality->name;
                    }
                    
                    // If logged in via Nafath, try to get nationality from nafath_data
                    if ($record->login_by === 'nafath' && $record->nafath_data) {
                        $nationalityCode = $record->nafath_data['NationalityCode'] ?? null;
                        if ($nationalityCode) {
                            return \App\Models\NafathNationalityCode::getNationalityNameFromCode($nationalityCode) ?? 'N/A';
                        }
                    }
                    
                    return 'N/A';
                }),
            Tables\Columns\TextColumn::make('date_of_birth')->searchable()->date('Y-m-d')->sortable(),
            Tables\Columns\TextColumn::make('gender')->searchable()->sortable()
                ->getStateUsing(fn($record) => $record->login_by === 'nafath' ? '-' : __('participant.' . $record->gender)),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('recovery_email')->searchable(),
            Tables\Columns\IconColumn::make('email_verified_at')
                ->getStateUsing(fn($record) => isset($record->email_verified_at))
                ->boolean()
                ->label('Account Verified')
                ->sortable(),

            Tables\Columns\TextColumn::make('applications')
                ->label('No of Programs Participated')
                ->getStateUsing(fn($record) => $record->applications->count())
                ->sortable(query: fn($query, $direction) => $query->withCount('applications')->orderBy('applications_count', $direction)),

            Tables\Columns\TextColumn::make('last_login_at')->default('Never')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('login_by')
                ->label('Login Method')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'nafath' => 'success',
                    'credentials' => 'info',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'nafath' => 'Nafath SSO',
                    'credentials' => 'Credentials',
                    default => 'Unknown',
                })
                ->searchable()
                ->sortable(),
            Tables\Columns\ToggleColumn::make('email_verified_at')
                ->label('Is Active')
                ->disabled(fn () => ! auth()->user()?->can('update Participant'))
                ->afterStateUpdated(function ($record, $state) {
                    $record->update(['email_verified_at' => $state ? now() : null]);
                }),
            Tables\Columns\TextColumn::make('created_at')->label('Registration Date')->searchable()->sortable(),
        ];
    }

    public static function details(): array
    {
        return [
            Section::make()
                ->columns()
                ->schema(
                    [
                        TextEntry::make('serial_number'),
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                        TextEntry::make('recovery_email')->label('Recovery Email'),
                        TextEntry::make('gender')
                            ->getStateUsing(fn($record) => __('participant.' . $record->gender))
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('date_of_birth')->date('Y-m-d'),
                        TextEntry::make('nationality.name')
                            ->getStateUsing(function ($record) {
                                // If nationality relationship exists, return it
                                if ($record->nationality) {
                                    return $record->nationality->name;
                                }
                                
                                // If logged in via Nafath, try to get nationality from nafath_data
                                if ($record->login_by === 'nafath' && $record->nafath_data) {
                                    $nationalityCode = $record->nafath_data['NationalityCode'] ?? null;
                                    if ($nationalityCode) {
                                        return \App\Models\NafathNationalityCode::getNationalityNameFromCode($nationalityCode) ?? 'N/A';
                                    }
                                }
                                
                                return 'N/A';
                            }),
                        TextEntry::make('country.name')
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('residenceCity.name')
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('educational_background')
                            ->getStateUsing(fn($record) => __('participant.' . $record->educational_background))
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('current_role')
                            ->getStateUsing(fn($record) => __('participant.' . $record->current_role))
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('place_of_work_study')
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('years_of_experience')
                            ->getStateUsing(fn($record) => __('participant.' . $record->years_of_experience))
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('experience_or_skills')
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('key_achievements')
                            ->visible(fn($record) => $record->login_by !== 'nafath'),
                        TextEntry::make('login_by')
                            ->label('Login Method')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'nafath' => 'Nafath SSO',
                                'credentials' => 'Credentials',
                                default => 'Unknown',
                            }),
                        TextEntry::make('last_login_at')
                            ->default('Never'),
                        TextEntry::make('created_at'),
                    ]
                ),
        ];
    }

    public function programApplications()
    {
        return $this->hasMany(ProgramApplication::class, 'participant_id');
    }

    public static function namesByIds(array $ids): array
    {
        return self::whereIn('id', $ids)->pluck('name')->toArray();
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function archive(): bool
    {
        $result = $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);


        return $result;
    }

    public function restore(): bool
    {
        $result = $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);


        return $result;
    }


    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }


    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Send OTP to recovery email for verification
     */
    public function sendRecoveryEmailOtp(string $recoveryEmail): string
    {
        // Additional check to ensure recovery email is different from main email
        if ($recoveryEmail === $this->email) {
            throw new \Exception(__('recovery_email.must_be_different'));
        }

        // Generate 6-digit OTP
        $otpCode = rand(100000, 999999);

        // Store the OTP and recovery email temporarily
        $this->update([
            'activation_code' => $otpCode,
            'recovery_email_temp' => $recoveryEmail,
        ]);

        // Send OTP to the recovery email
        $this->email = $recoveryEmail;
        $this->notify(new \App\Notifications\Participant\RecoveryEmailOtpVerification($otpCode, $recoveryEmail));

        return $otpCode;
    }


    /**
     * Verify OTP and add recovery email to profile
     */
    public function verifyRecoveryEmailOtp(string $otpCode): bool
    {
        if ($this->activation_code !== $otpCode) {
            return false;
        }

        // Add recovery email to profile and clear temporary data
        $this->update([
            'recovery_email' => $this->recovery_email_temp,
            'activation_code' => null,
            'recovery_email_temp' => null,
        ]);

        return true;
    }

}
