<?php

namespace App\Filament\Resources\ParticipantResource\RelationManagers;

use App\Filament\Traits\ManageableRelation;
use App\Models\ProgramApplication;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationsRelationManager extends RelationManager
{
    use ManageableRelation;

    protected static string $relationship = 'applications';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        // Filter applications by admin's assigned programs (unless super admin)
        if ($user && !$user->isSuperAdmin()) {
            // Get program IDs the user has access to
            $programIds = \App\Models\UserProgram::where('user_id', $user->id)
                ->pluck('program_id')
                ->toArray();
            
            if (!empty($programIds)) {
                $table->modifyQueryUsing(function ($query) use ($programIds) {
                    $query->whereIn('program_id', $programIds);
                });
            } else {
                // If user has no assigned programs, show nothing
                $table->modifyQueryUsing(function ($query) {
                    $query->whereRaw('1 = 0');
                });
            }
        }
        
        return $table
            ->recordTitleAttribute('id')
            ->columns(ProgramApplication::columns())
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Check authorization before allowing approve action
                        $user = auth()->user();
                        if (!$user || !$user->can('update ProgramApplication')) {
                            abort(403, 'You do not have permission to approve this application.');
                        }
                        
                        // Check program access (unless super admin)
                        if (!$user->isSuperAdmin() && $record->program && !$record->program->canAccessProgram()) {
                            abort(403, 'You do not have access to this program.');
                        }
                        
                        $record->approve();
                    })
                    ->visible(fn ($record) => !$record->isArchived() && $record->isPending() && auth()->user()?->can('update ProgramApplication')),


                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Check authorization before allowing reject action
                        $user = auth()->user();
                        if (!$user || !$user->can('update ProgramApplication')) {
                            abort(403, 'You do not have permission to reject this application.');
                        }
                        
                        // Check program access (unless super admin)
                        if (!$user->isSuperAdmin() && $record->program && !$record->program->canAccessProgram()) {
                            abort(403, 'You do not have access to this program.');
                        }
                        
                        $record->reject();
                    })
                    ->visible(fn ($record) => !$record->isArchived() && $record->isPending() && auth()->user()?->can('update ProgramApplication')),


                Tables\Actions\ViewAction::make()
                    ->authorize(fn ($record) => \App\Filament\Resources\ProgramApplicationResource::canView($record)),

                Tables\Actions\DeleteAction::make()
                    ->authorize(fn ($record) => \App\Filament\Resources\ProgramApplicationResource::canDelete($record))
                    ->visible(fn ($record) => auth()->user()?->can('delete ProgramApplication')),

            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(ProgramApplication::details());
    }
}
