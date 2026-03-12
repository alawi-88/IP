<?php
/**
 * Fix admin blade: update viability score to handle format without breakdown,
 * and update phases rendering to handle 'name' key.
 */

$bladeFile = '/var/www/resources/views/filament/resources/venture-resource/relation-managers/partials/section-content.blade.php';
$content = file_get_contents($bladeFile);

// Fix 1: Update viability score to not require 'breakdown'
$oldViability = <<<'BLADE'
{{-- VIABILITY SCORE --}}
@elseif(isset($d['score']) && isset($d['breakdown']))
BLADE;

$newViability = <<<'BLADE'
{{-- VIABILITY SCORE --}}
@elseif(isset($d['score']) && (isset($d['breakdown']) || isset($d['rating']) || isset($d['justification'])))
BLADE;

$content = str_replace($oldViability, $newViability, $content);

// Fix 2: After the breakdown bars, add support for rating/justification when no breakdown
$oldBreakdownEnd = <<<'BLADE'
    @if(is_array($d['breakdown']))
        <div class="space-y-2">
            @foreach($d['breakdown'] as $item)
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-600 min-w-[100px]">{{ $item['label'] ?? $item['category'] ?? '' }}</span>
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500 rounded-full" style="width: {{ min($item['score'] ?? $item['value'] ?? 0, 100) }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-700 w-8 text-right">{{ $item['score'] ?? $item['value'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    @endif
BLADE;

$newBreakdownEnd = <<<'BLADE'
    @if(isset($d['breakdown']) && is_array($d['breakdown']))
        <div class="space-y-2">
            @foreach($d['breakdown'] as $item)
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-600 min-w-[100px]">{{ $item['label'] ?? $item['category'] ?? '' }}</span>
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500 rounded-full" style="width: {{ min($item['score'] ?? $item['value'] ?? 0, 100) }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-700 w-8 text-right">{{ $item['score'] ?? $item['value'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    @endif
    @if(isset($d['rating']))
        <p class="text-center text-sm font-semibold mt-2" style="color: var(--primary-500)">{{ $d['rating'] }}</p>
    @endif
    @if(isset($d['justification']))
        <p class="text-xs text-gray-600 mt-2 text-center">{{ $d['justification'] }}</p>
    @endif
BLADE;

$content = str_replace($oldBreakdownEnd, $newBreakdownEnd, $content);

// Fix 3: Update phases/timeline to also check for 'name' key (in addition to existing 'name' ?? 'title')
// The blade already uses $phase['name'] ?? $phase['title'], so that's fine.
// But the stages section uses $stage['title'] ?? $stage['name'] - let's ensure phases also handle 'phase_name'
$oldPhaseName = "\$phase['name'] ?? \$phase['title'] ?? ''";
$newPhaseName = "\$phase['name'] ?? \$phase['phase_name'] ?? \$phase['title'] ?? ''";
$content = str_replace($oldPhaseName, $newPhaseName, $content);

// Fix 4: Update stages to also handle 'name' first
$oldStageName = "\$stage['title'] ?? \$stage['name'] ?? ''";
$newStageName = "\$stage['name'] ?? \$stage['title'] ?? \$stage['stage'] ?? ''";
$content = str_replace($oldStageName, $newStageName, $content);

file_put_contents($bladeFile, $content);
echo "Admin blade updated successfully.\n";

// Verify changes
echo "\nVerifying viability score check:\n";
preg_match('/VIABILITY SCORE.*?\n.*?\n/', $content, $matches);
echo $matches[0] ?? "Not found\n";

echo "\nDone.\n";
