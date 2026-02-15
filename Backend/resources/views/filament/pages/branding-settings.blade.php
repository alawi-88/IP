<x-filament::page>
    {{ $this->form }}
    @if(auth()->user()->can('update BrandingSettings'))
    <x-filament::button wire:click="save" class="mt-4">
        Save Settings
    </x-filament::button>
    @endif
</x-filament::page>
