<?php

namespace App\Filament\Resources\MentorResource\Pages;

use App\Filament\Resources\MentorResource;
use App\Models\Mentor;
use App\Notifications\Mentor\MentorAutoCredentials;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateMentor extends CreateRecord
{
    protected static string $resource = MentorResource::class;
    
    protected ?string $plainPassword = null;
    
    protected ?array $competitions = null;

    public function form(Form $form): Form
    {
        return $form->schema(Mentor::form());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store competitions to sync after creation
        $this->competitions = $data['competitions'] ?? [];
        
        // Remove competitions from data as it's not a fillable field
        unset($data['competitions']);
        
        // Generate random password
        $this->plainPassword = Str::random(16);
        
        // Hash the password
        $data['password'] = Hash::make($this->plainPassword);
        
        // Set approved_at if status is approved
        if (isset($data['status']) && $data['status'] === 'approved') {
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        } else {
            $data['approved_at'] = null;
            $data['approved_by'] = null;
        }
        
        // Set rejected_at if status is rejected
        if (isset($data['status']) && $data['status'] === 'rejected') {
            $data['rejected_at'] = now();
        } else {
            $data['rejected_at'] = null;
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync competitions if any were selected
        if (isset($this->competitions) && !empty($this->competitions)) {
            $this->record->competitions()->sync($this->competitions);
        }
        
        // Send credentials email
        if ($this->record && isset($this->plainPassword)) {
            $this->record->notify(new MentorAutoCredentials($this->record, $this->plainPassword));
        }
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.mentors.index');
    }
}
