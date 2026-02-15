<?php

namespace App\Filament\Pages;

use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use App\Models\Social as SocialModel;
use Illuminate\Database\Eloquent\Model;

class Social extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected ?string $heading = 'Social Links';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static string $view = 'filament.pages.social';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 29;

    public function mount(): void
    {
        $this->form->fill(SocialModel::all()->pluck('url', 'name')->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('facebook')
                    ->url(),

                TextInput::make('x')
                    ->url(),

                TextInput::make('instagram')
                    ->url(),

                TextInput::make('linkedin')
                    ->url(),

                TextInput::make('youtube')
                    ->url(),

                TextInput::make('snapchat')
                    ->url(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        $canEdit = auth()->user()?->can('update Social');

        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save')
                ->visible($canEdit),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                SocialModel::updateOrCreate(['name' => $key], ['url' => $value]);
            }
        } catch (Halt $exception) {
            return;
        }

        Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view Social') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update Social');
    }
}
