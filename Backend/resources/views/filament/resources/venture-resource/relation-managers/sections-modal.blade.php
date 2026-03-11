<div class="space-y-4">
    @forelse ($sections as $section)
        <div class="border rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-800">{{ $section->label_en ?: ucwords(str_replace(['_', '-'], ' ', $section->slug)) }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        @if($section->status === 'completed') bg-green-100 text-green-700
                        @elseif($section->status === 'failed') bg-red-100 text-red-700
                        @elseif($section->status === 'generating') bg-blue-100 text-blue-700
                        @else bg-gray-100 text-gray-600
                        @endif
                    ">{{ ucfirst($section->status) }}</span>
                    @if(!$section->is_visible)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">Hidden</span>
                    @endif
                </div>
                <span class="text-xs text-gray-500">{{ $section->component_type }}</span>
            </div>
            @if($section->content)
                <details class="mt-2">
                    <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">View Content (JSON)</summary>
                    <pre class="mt-2 text-xs bg-white p-3 rounded border overflow-auto max-h-60">{{ json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif
        </div>
    @empty
        <p class="text-gray-500 text-center py-4">No sections found</p>
    @endforelse
</div>
