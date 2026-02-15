@php
    $paths = $getState();
    if (! is_array($paths)) {
        $paths = empty($paths) ? [] : [$paths];
    }
@endphp

@if (empty($paths))
    <span class="text-gray-400">{{ __('No files') }}</span>
@else
    <div class="space-y-1">
        @foreach ($paths as $path)
            @php
                $url = Str::startsWith($path, 'http') ? $path : Storage::disk('public')->url($path);
                $name = basename($path);
                $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $path);
            @endphp
            @if ($isImage)
                <a href="{{ $url }}" target="_blank" class="inline-flex flex-col items-start">
                    <img src="{{ $url }}" alt="{{ $name }}" class="h-10 w-10 object-cover rounded" title="{{ $name }}" />
{{--                    <span class="text-xs text-gray-400 mt-1">{{ $name }}</span>--}}
                </a>
            @else
                <a href="{{ $url }}" target="_blank" class="text-primary underline">{{ $name }}</a>
            @endif
        @endforeach
    </div>
@endif


