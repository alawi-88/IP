<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;
use Filament\Tables;
use Filament\Forms;

/**
 * @method static updateOrCreate(string[] $array, array $array1)
 * @method static unpublished()
 * @method static published()
 */
class Page extends Model
{
    use HasTranslations, LogsActivity, HasActivityLog;

    protected array $logFields = [
        'content',
        'is_published'
    ];

    protected string $moduleName = 'Page';
    protected string $logName = 'page';

    public array $translatable = ['title', 'content'];

    protected $fillable = ['title', 'content', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    public function scopeUnpublished($query)
    {
        return $query->where('is_published', 0);
    }

    public function getContentAttribute($value): string
    {
        return str($value)->sanitizeHtml();
    }

    public static function details(): array
    {
        return [
            Section::make('Basic Information')
                ->columns(3)
                ->schema([
                    TextEntry::make('title')->label('Title'),
                    IconEntry::make('is_published')->boolean()->label('Published'),
                    TextEntry::make('updated_at')->label('Last Updated'),
                ]),

            Section::make('Content')
                ->columns()
                ->schema([
                    TextEntry::make('content')->hiddenLabel()->html()->columnSpanFull()
                ]),
        ];
    }

    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),

            Tables\Columns\ToggleColumn::make('is_published')
                ->label('Published')
                ->disabled(fn () => ! auth()->user()?->can('update Page'))
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Last Updated')
                ->since()
                ->sortable(),

        Tables\Columns\TextColumn::make('last_updated_by')
            ->label('Last Updated By')
            ->getStateUsing(function ($record) {
                $activity = \App\Models\ActivityLog::where('log_name', 'page')
                    ->where('subject_id', $record->id)
                    ->orderByDesc('updated_at')
                    ->first();

                if ($activity && $activity->causer_id) {
                    $user = \App\Models\User::find($activity->causer_id);
                    return $user ? $user->name : '-';
                }

                return '-';
            })
            ->sortable(false)
            ->searchable(false),
        ];
    }

    public static function form(): array
    {
        return [
            Forms\Components\RichEditor::make('title.en')
                ->label('Title')
                ->disabled()
                ->dehydrated(),

            Forms\Components\TextInput::make('title.ar')
                ->label('العنوان')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->disabled()
                ->dehydrated(),
           
            Forms\Components\RichEditor::make('content.en')
                ->label('Content')
                ->required()
                ->columnSpanFull(),

            Forms\Components\RichEditor::make('content.ar')
                ->label('المحتوى')
                ->extraFieldWrapperAttributes(['class' => 'text-right'])
                ->required()->columnSpanFull(),
            
            Forms\Components\Checkbox::make('is_published')
                ->label('Published'),
        ];
    }
}
