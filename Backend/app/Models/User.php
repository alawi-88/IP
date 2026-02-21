<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Althinect\FilamentSpatieRolesPermissions\Concerns\HasSuperAdmin;
use App\Notifications\NewAdminAccount;
use App\Notifications\UpdateAdminAccount;
use App\Traits\HasActivityLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method static updateOrCreate(string[] $array, array $array1)
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LogsActivity, HasActivityLog,HasRoles,HasSuperAdmin;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     *
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
        'is_archived',
        'archived_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected array $logFields = [
        'name',
        'email',
        'roles_list',
        'permissions_list',
        'is_archived',
        'archived_at',
    ];

    protected string $moduleName = 'User';
    protected string $logName = 'user';


    protected static function boot(): void
    {
        parent::boot();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Prevent archived users from accessing the panel
        return !$this->isArchived();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
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
            ->belongsToMany(Program::class, 'user_programs')
            ->using(UserProgram::class)
            ->withTimestamps();
    }

    public static function details(): array
    {
        return [
            Section::make()
                ->columns()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                    TextEntry::make('last_login_at')->default('Never'),
                    TextEntry::make('created_at')->date(),
                ])
        ];
    }
}
