<x-filament-panels::page>
    {{ $this->infolist }}

    <x-filament-panels::resources.relation-managers
        :active-manager="$this->activeRelationManager"
        :managers="$this->getRelationManagers()"
        :owner-record="$this->getRecord()"
        :page-class="static::class"
    />
</x-filament-panels::page>
