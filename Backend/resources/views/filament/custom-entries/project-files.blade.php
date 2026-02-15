<div class="space-y-2">
    @if(empty($files) || count($files) === 0)
        <div class="text-sm text-gray-500">
            No files found / لم يتم العثور على ملفات
        </div>
    @else
        <div class="grid grid-cols-2 gap-2">
            @foreach($files as $file)
                <div class="border rounded p-2">
                    @if($file['isImage'] ?? false)
                        <a href="{{ $file['url'] }}" target="_blank" title="{{ $file['filename'] }}">
                            <img src="{{ $file['url'] }}" alt="{{ $file['filename'] }}" class="w-full h-32 object-cover rounded shadow" />
                        </a>
                        <div class="text-xs text-gray-600 mt-1 truncate">{{ $file['filename'] }}</div>
                    @else
                        <a href="{{ $file['url'] }}" target="_blank" class="flex items-center gap-2 text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-sm truncate">{{ $file['filename'] }}</span>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>


