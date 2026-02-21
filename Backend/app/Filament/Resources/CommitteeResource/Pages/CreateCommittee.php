<?php

namespace App\Filament\Resources\CommitteeResource\Pages;

use App\Filament\Resources\CommitteeResource;
use App\Models\Committee;
use App\Models\ProgramJudge;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateCommittee extends CreateRecord
{
    protected static string $resource = CommitteeResource::class;

    public function form(Form $form): Form
    {
        return $form->schema(Committee::form());
    }
}
