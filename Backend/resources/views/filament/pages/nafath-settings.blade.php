<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}
        
        <div class="flex justify-end space-x-3">
            <x-filament::button
                wire:click="save"
                color="primary"
                icon="heroicon-o-check"
            >
                Save Settings
            </x-filament::button>
            
            <x-filament::button
                wire:click="testConnection"
                color="gray"
                icon="heroicon-o-wifi"
            >
                Test Connection
            </x-filament::button>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Important Notes
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li>When Nafath SSO is enabled, users will see a "Login with Nafath" option on the login page.</li>
                            <li>Valid Client ID and Client Secret are required to enable Nafath SSO.</li>
                            <li>Credentials will be validated with the MIP service before saving.</li>
                            <li>Make sure to configure the correct Redirect URI in your MIP client settings.</li>
                            <li>Disabling Nafath SSO will hide the login option but won't affect existing sessions.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
