<div class="space-y-1">
    <div class="text-sm font-medium">
        {{ $label ?? $filename }}
    </div>
<br>
    @if ($isImage)
        <a href="{{ $url }}" target="_blank" title="{{ $filename }}">
            <img src="{{ $url }}" alt="{{ $filename }}" class="w-20 rounded shadow border" />
        </a>
    @else
        <a href="{{ $url }}" target="_blank" class="text-blue-600 underline">
            📎 {{ $filename }}
        </a>
    @endif
</div>
