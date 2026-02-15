<?php

namespace App\Filament\Resources\JudgeResource\Pages;

use App\Filament\Resources\JudgeResource;
use App\Models\Judge;
use App\Notifications\NewJudgeAccount;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateJudge extends CreateRecord
{
    protected static string $resource = JudgeResource::class;

    protected ?string $generatedPassword = null;

    public function form(Form $form): Form
    {
        return $form->schema(Judge::form());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->generatedPassword = Str::random(10);
        $data['password'] = bcrypt($this->generatedPassword);
        $data['email_verified_at'] = now();

        return $data;
    }


    protected function afterCreate(): void
    {
        $this->record->notify(new NewJudgeAccount($this->record, $this->generatedPassword));
    }
}
