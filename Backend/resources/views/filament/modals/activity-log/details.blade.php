<div class="space-y-2">
    <div>
        <strong>Performed By:</strong> {{ $record->causer?->name ?? 'System' }}
    </div>

    <div>
        <strong>On:</strong> {{ $record->created_at->format('Y-m-d H:i:s') }}
    </div>

    <div>
        <strong>Changes:</strong>
        <ul class="list-disc list-inside mt-2 text-sm text-gray-700">
            @forelse ($record->changes_list as $key => $change)
                <li class="mb-4">
                    <strong class="text-white">{{ ucfirst($key) }}:</strong>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        {{-- Old Value --}}
                        <div>
                            <div class="bg-red-800 text-white text-xs font-mono p-3 rounded">
                                <div class="font-bold mb-1">Old:</div>
                                <pre class="whitespace-pre-wrap">
{{ is_array($change['old']) ? json_encode($change['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($change['old'] ?? 'N/A') }}
                                </pre>
                            </div>
                        </div>

                        {{-- New Value --}}
                        <div>
                            <div class="bg-green-800 text-white text-xs font-mono p-3 rounded">
                                <div class="font-bold mb-1">New:</div>
                                <pre class="whitespace-pre-wrap">
{{ is_array($change['new']) ? json_encode($change['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $change['new'] }}
                                </pre>
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li>No data changes were recorded.</li>
            @endforelse
        </ul>
    </div>
</div>
