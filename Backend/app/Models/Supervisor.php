<?php

namespace App\Models;

use Althinect\FilamentSpatieRolesPermissions\Concerns\HasSuperAdmin;
use App\Notifications\NewSupervisorAccount;
use App\Notifications\UpdateSupervisorAccount;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Supervisor extends Authenticatable
{
    use Notifiable, LogsActivity, HasActivityLog,HasRoles,HasSuperAdmin;

    protected $table = 'users';


    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    protected array $logFields = [
        'name',
        'email',
        'roles_list',
        'permissions_list',
    ];

    protected string $moduleName = 'Supervisor';
    protected string $logName = 'supervisor';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($supervisor) {
            $password = Str::random(10);
            $supervisor->password = bcrypt($password);
//            $supervisor->notify(new NewSupervisorAccount($supervisor, $password));
        });

        static::updating(function ($supervisor) {
            $password = Str::random(10);
            $supervisor->password = bcrypt($password);
//            $supervisor->notify(new UpdateSupervisorAccount($supervisor, $password));
        });
    }

    public function getRolesListAttribute(): string
    {
        return $this->roles->pluck('name')->join(', ');
    }

    public function getPermissionsListAttribute(): string
    {
        return $this->permissions->pluck('name')->join(', ');
    }


    public function programs(): BelongsToMany
    {
        return $this
            ->belongsToMany(Program::class, 'supervisor_programs')
            ->using(SupervisorProgram::class)
            ->withTimestamps();
    }

    public static function form(): array
    {
        return [
            Forms\Components\Select::make('programs')
                ->label('Programs')
                ->multiple()
                ->columnSpanFull()
                ->required()
                ->relationship('programs', 'title')
                ->options(fn() => Program::pluck('title', 'id')->toArray()),

            Forms\Components\TextInput::make('name')->label('Name')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('email')->label('Email')
                ->required()
                ->unique('users', 'email', ignoreRecord: true)
                ->columnSpanFull(),

            Forms\Components\Select::make('roles')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->searchable()
                ->columnSpanFull(),
        ];
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('email')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('role')
                ->label('Role')
                ->badge()
                ->getStateUsing(fn($record) => $record->roles->pluck('name')->map(fn($role) => ucfirst($role))->join(', ')),

            Tables\Columns\TextColumn::make('programs_count')
                ->label('Programs')
                ->counts('programs')
                ->sortable(),

            Tables\Columns\TextColumn::make('last_login_at')
                ->label('Last Login')
                ->formatStateUsing(fn($state) => $state ? $state->format('Y-m-d H:i') : 'Never')
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
        ];
    }



    public static function details(): array
    {
        return [
            Section::make()
                ->columns()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                    TextEntry::make('role')
                        ->badge()
                        ->formatStateUsing(fn($record) => $record->roles->pluck('name')->map(fn($role) => ucfirst($role))->join(', ')),
                    TextEntry::make('last_login_at')->default('Never'),
                    TextEntry::make('created_at')->date(),
                    TextEntry::make('programs')
                        ->formatStateUsing(fn($record) => $record->programs->pluck('title')->implode(', ')),
                ])
        ];
    }
}
