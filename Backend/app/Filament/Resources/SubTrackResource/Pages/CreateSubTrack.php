<?php

namespace App\Filament\Resources\SubTrackResource\Pages;

use App\Filament\Resources\SubTrackResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CreateSubTrack extends CreateRecord
{
    protected static string $resource = SubTrackResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
                throw ValidationException::withMessages([
                    'name.en' => [__('A sub track with this name already exists in the selected track.')],
                ]);
            }
            throw $e;
        }
    }
}
