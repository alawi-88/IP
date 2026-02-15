<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasUser;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\ViewField;

class CustomProfileComponent extends Component implements HasForms
{
    use InteractsWithForms;
    use HasUser;
    use HasSort;

    public $user;
    public ?array $data = [];

    protected string $view = 'filament.pages.edit-profile';
    protected static int $sort = 0;

    public function mount(): void
    {
        $this->user = $this->getUser();

        $this->data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'roles' => $this->user->getRoleNames()->implode(', '),
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profile Information')
                    ->aside()
                    ->description('View your profile information and roles.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->disabled(),

                        TextInput::make('email')
                            ->label('Email')
                            ->disabled(),

                        TextInput::make('roles')
                            ->label('Roles')
                            ->disabled(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $this->user->update([
                'name' => $data['name'] ?? $this->user->name,
            ]);
        } catch (\Exception $exception) {
            return;
        }

        Notification::make()
            ->success()
            ->title('Saved successfully')
            ->send();
    }

    public function render(): View
    {
        return view('livewire.custom-profile-component');
    }
}
