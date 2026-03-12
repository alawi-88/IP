<div class="space-y-4">
    @forelse ($sections as $section)
        <div class="border rounded-lg overflow-hidden bg-white">
            {{-- Section Header --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b">
                <div class="flex items-center gap-2">
                    @if($section->display_config && !empty($section->display_config?->icon))
                        @php
                            try {
                                $iconSvg = svg('heroicon-o-' . $section->display_config->icon, 'w-5 h-5 text-gray-500')->toHtml();
                            } catch (\Exception $e) {
                                $iconSvg = null;
                            }
                        @endphp
                        @if($iconSvg)
                            {!! $iconSvg !!}
                        @else
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        @endif
                    @endif
                    <span class="font-semibold text-gray-800">{{ $section->label_en ?: ucwords(str_replace(['_', '-'], ' ', $section->slug)) }}</span>
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
                <span class="text-xs text-gray-400 font-mono">{{ $section->display_config?->component_type ?? $section->component_type }}</span>
            </div>

            {{-- Section Content --}}
            @if($section->content)
                @php
                    $rawContent = $section->content;
                    if (is_array($rawContent)) {
                        $data = $rawContent;
                    } elseif (is_string($rawContent)) {
                        $decoded = json_decode($rawContent, true);
                        $data = is_array($decoded) ? $decoded : ['body' => $rawContent];
                    } elseif (is_object($rawContent)) {
                        $data = json_decode(json_encode($rawContent), true);
                    } else {
                        $data = [];
                    }
                    $componentType = $section->display_config?->component_type ?? $section->component_type;
                @endphp

                <div class="px-4 py-3">
                    @include('filament.resources.venture-resource.relation-managers.partials.section-content', [
                        'data' => $data,
                        'componentType' => $componentType,
                    ])
                </div>

                {{-- Raw JSON Toggle --}}
                <details class="border-t">
                    <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 px-4 py-2 bg-gray-50">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            View Raw JSON
                        </span>
                    </summary>
                    <pre class="text-xs bg-gray-50 p-4 overflow-auto max-h-48 border-t">{{ json_encode($section->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @else
                <div class="px-4 py-6 text-center text-sm text-gray-400">No content generated</div>
            @endif

            {{-- Prompt Sent Toggle --}}
            @if($section->prompt_sent)
                <details class="border-t">
                    <summary class="text-xs cursor-pointer hover:text-blue-600 px-4 py-2 bg-blue-50 text-blue-500">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            View Prompt Sent to AI
                        </span>
                    </summary>
                    <pre class="text-xs bg-blue-50 p-4 overflow-auto max-h-64 border-t border-blue-100 whitespace-pre-wrap text-gray-700">{{ $section->prompt_sent }}</pre>
                </details>
            @endif

            {{-- Raw AI Response Toggle --}}
            @if($section->raw_response)
                <details class="border-t">
                    <summary class="text-xs cursor-pointer hover:text-purple-600 px-4 py-2 bg-purple-50 text-purple-500">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            View Raw AI Response
                        </span>
                    </summary>
                    <pre class="text-xs bg-purple-50 p-4 overflow-auto max-h-64 border-t border-purple-100 whitespace-pre-wrap text-gray-700">{{ $section->raw_response }}</pre>
                </details>
            @endif

            {{-- Error Message --}}
            @if($section->error_message)
                <div class="border-t bg-red-50 px-4 py-2">
                    <p class="text-xs text-red-600">
                        <span class="font-semibold">Error:</span> {{ $section->error_message }}
                    </p>
                </div>
            @endif
        </div>
    @empty
        <p class="text-gray-500 text-center py-8">No sections found</p>
    @endforelse
</div>
