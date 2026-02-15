<?php

namespace App\Filament\Resources\StageResource\Pages;

use App\Filament\Resources\StageResource;
use App\Models\Stage;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateStage extends CreateRecord
{
    protected static string $resource = StageResource::class;

    public function form(Form $form): Form
    {
        return $form->schema(Stage::form());
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.stages.index');
    }
}
