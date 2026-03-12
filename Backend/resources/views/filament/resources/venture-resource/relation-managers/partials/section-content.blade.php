@php
    // Try to unwrap nested content (seeder wraps data under component-type keys)
    $unwrapped = $data;
    $unwrappedFromKey = null;
    if (is_array($data)) {
        $typeKeys = [$componentType, 'stat_cards', 'swot_grid', 'comparison_table', 'risk_matrix',
            'progress_bars', 'key_value', 'funnel_chart', 'persona_cards', 'timeline',
            'pricing_cards', 'viability_score', 'text_content', 'pestel', 'funding_strategy',
            'growth_channels', 'partnerships', 'differentiators', 'tech_architecture',
            'development_roadmap', 'launch_plan', 'mvp_definition', 'milestones',
            'journey_timeline', 'sales_funnel', 'customer_journey'];
        foreach ($typeKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $unwrapped = $data[$key];
                $unwrappedFromKey = $key;
                break;
            }
        }
        // Handle { data: {...}, component_type: "..." } wrapper
        if (isset($data['data']) && is_array($data['data']) && isset($data['component_type'])) {
            $unwrapped = $data['data'];
            $componentType = $data['component_type'];
        }
        // If data was unwrapped from a structural key different from the declared componentType,
        // update componentType to match the actual data structure so the correct renderer is used.
        if ($unwrappedFromKey && $unwrappedFromKey !== $componentType && $unwrappedFromKey !== 'text_content') {
            $componentType = $unwrappedFromKey;
        }
    }
    $d = $unwrapped;

    // Determine if data actually looks like text content (not structured sections)
    $hasStructuredSections = is_array($d) && isset($d['title']) && isset($d['sections']) && is_array($d['sections']);
    $isActuallyTextContent = !$hasStructuredSections && (
        is_string($d)
        || (is_array($d) && (isset($d['body']) || isset($d['text']) || isset($d['content'])))
    );
@endphp

{{-- TEXT CONTENT --}}
@if(($componentType === 'text_content' && $isActuallyTextContent) || (isset($d['body']) && is_string($d['body'])))
    <div class="prose prose-sm max-w-none text-gray-700">
        {!! nl2br(e($d['body'] ?? $d['text'] ?? $d['content'] ?? (is_string($d) ? $d : ''))) !!}
    </div>

{{-- STAT CARDS --}}
@elseif(
    (isset($d['metrics']) && is_array($d['metrics']))
    || (isset($d['cards']) && is_array($d['cards']))
    || (isset($d['stats']) && is_array($d['stats']))
    || (is_array($d) && array_is_list($d) && count($d) > 0 && is_array($d[0] ?? null) && (isset($d[0]['title']) || isset($d[0]['label'])) && isset($d[0]['value']))
)
    @php $statItems = $d['metrics'] ?? $d['cards'] ?? $d['stats'] ?? (is_array($d) && array_is_list($d) ? $d : []); @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach($statItems as $metric)
            <div class="bg-gray-50 rounded-lg p-3 text-center border">
                <p class="text-xs text-gray-500 mb-1">{{ $metric['label'] ?? $metric['title'] ?? $metric['name'] ?? '' }}</p>
                <p class="text-lg font-bold text-gray-800">{{ $metric['value'] ?? '' }}</p>
                @if(isset($metric['change']))
                    <p class="text-xs {{ str_contains($metric['change'] ?? '', '-') ? 'text-red-500' : 'text-green-500' }}">{{ $metric['change'] }}</p>
                @endif
                @if(isset($metric['trend']))
                    <p class="text-xs {{ $metric['trend'] === 'up' ? 'text-green-500' : ($metric['trend'] === 'down' ? 'text-red-500' : 'text-gray-400') }}">
                        {{ $metric['trend'] === 'up' ? '↑' : ($metric['trend'] === 'down' ? '↓' : '→') }}
                        {{ ucfirst($metric['trend']) }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>

{{-- SWOT GRID --}}
@elseif(isset($d['strengths']) || isset($d['weaknesses']) || isset($d['opportunities']) || isset($d['threats']))
    <div class="grid grid-cols-2 gap-3">
        @foreach(['strengths' => ['Strengths', 'bg-green-50', 'text-green-700', 'border-green-200'], 'weaknesses' => ['Weaknesses', 'bg-red-50', 'text-red-700', 'border-red-200'], 'opportunities' => ['Opportunities', 'bg-blue-50', 'text-blue-700', 'border-blue-200'], 'threats' => ['Threats', 'bg-yellow-50', 'text-yellow-700', 'border-yellow-200']] as $key => [$label, $bg, $text, $border])
            @if(isset($d[$key]))
                <div class="{{ $bg }} rounded-lg p-3 border {{ $border }}">
                    <p class="text-xs font-bold {{ $text }} uppercase mb-2">{{ $label }}</p>
                    @if(is_array($d[$key]))
                        <ul class="space-y-1">
                            @foreach($d[$key] as $item)
                                <li class="text-xs text-gray-700 flex gap-1.5">
                                    <span class="mt-1 w-1 h-1 rounded-full {{ $text }} flex-shrink-0 opacity-60"></span>
                                    {{ is_string($item) ? $item : ($item['text'] ?? $item['title'] ?? json_encode($item)) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        @endforeach
    </div>

{{-- KEY VALUE --}}
@elseif(isset($d['items']) && is_array($d['items']) && isset($d['items'][0]['key']))
    <div class="space-y-2">
        @foreach($d['items'] as $item)
            <div class="flex gap-3 text-sm">
                <span class="font-medium text-gray-600 min-w-[120px]">{{ $item['key'] }}</span>
                <span class="text-gray-800">{{ $item['value'] ?? '' }}</span>
            </div>
        @endforeach
    </div>

{{-- RISK MATRIX --}}
@elseif(
    (isset($d['risks']) && is_array($d['risks']))
    || (is_array($d) && array_is_list($d) && count($d) > 0 && is_array($d[0] ?? null) && (isset($d[0]['risk']) || isset($d[0]['severity']) || isset($d[0]['impact'])))
)
    @php $riskItems = $d['risks'] ?? (is_array($d) && array_is_list($d) ? $d : []); @endphp
    <div class="space-y-2">
        @foreach($riskItems as $risk)
            @php
                $severity = strtolower($risk['severity'] ?? $risk['impact'] ?? '');
                $severityColor = match($severity) {
                    'high', 'critical' => 'bg-red-100 text-red-700',
                    'medium' => 'bg-yellow-100 text-yellow-700',
                    'low' => 'bg-green-100 text-green-700',
                    default => 'bg-gray-100 text-gray-600'
                };
            @endphp
            <div class="flex items-start gap-3 bg-gray-50 rounded-lg p-3 border">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $severityColor }}">{{ ucfirst($severity) }}</span>
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $risk['title'] ?? $risk['name'] ?? $risk['risk'] ?? '' }}</p>
                    @if(isset($risk['description']))
                        <p class="text-xs text-gray-500 mt-0.5">{{ $risk['description'] }}</p>
                    @endif
                    @if(isset($risk['likelihood']))
                        <p class="text-xs text-gray-400 mt-0.5">Likelihood: {{ ucfirst($risk['likelihood']) }}</p>
                    @endif
                    @if(isset($risk['mitigation']))
                        <p class="text-xs text-blue-600 mt-1">Mitigation: {{ $risk['mitigation'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

{{-- TIMELINE / STAGES --}}
@elseif(isset($d['stages']) && is_array($d['stages']))
    <div class="space-y-3">
        @foreach($d['stages'] as $i => $stage)
            <div class="flex gap-3">
                <div class="flex flex-col items-center">
                    <div class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</div>
                    @if(!$loop->last)
                        <div class="w-0.5 flex-1 bg-gray-200 mt-1"></div>
                    @endif
                </div>
                <div class="flex-1 pb-4">
                    <p class="text-sm font-semibold text-gray-800">{{ $stage['name'] ?? $stage['title'] ?? $stage['stage'] ?? '' }}</p>
                    @if(isset($stage['description']))
                        <p class="text-xs text-gray-500 mt-0.5">{{ $stage['description'] }}</p>
                    @endif
                    @if(isset($stage['duration']))
                        <span class="text-xs text-gray-400">{{ $stage['duration'] }}</span>
                    @endif
                    @foreach(['actions', 'touchpoints', 'deliverables', 'tasks', 'activities', 'milestones'] as $listKey)
                        @if(isset($stage[$listKey]) && is_array($stage[$listKey]))
                            <p class="text-xs font-medium text-gray-500 mt-1.5 mb-0.5">{{ ucfirst($listKey) }}:</p>
                            <ul class="space-y-0.5">
                                @foreach($stage[$listKey] as $listItem)
                                    <li class="text-xs text-gray-600">• {{ is_string($listItem) ? $listItem : ($listItem['name'] ?? $listItem['title'] ?? $listItem['text'] ?? json_encode($listItem, JSON_UNESCAPED_UNICODE)) }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

{{-- COMPARISON TABLE --}}
@elseif(isset($d['headers']) && isset($d['rows']))
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($d['headers'] as $header)
                        <th class="px-3 py-2 text-left font-semibold text-gray-600 border-b">{{ is_string($header) ? $header : ($header['label'] ?? '') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($d['rows'] as $row)
                    <tr class="border-b last:border-b-0">
                        @if(is_array($row) && !isset($row['cells']))
                            @foreach($row as $cell)
                                <td class="px-3 py-2 text-gray-700">{{ is_string($cell) ? $cell : json_encode($cell) }}</td>
                            @endforeach
                        @elseif(isset($row['cells']))
                            @foreach($row['cells'] as $cell)
                                <td class="px-3 py-2 text-gray-700">{{ is_string($cell) ? $cell : ($cell['value'] ?? json_encode($cell)) }}</td>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

{{-- PERSONA CARDS --}}
@elseif(isset($d['name']) && isset($d['role']) && (isset($d['goals']) || isset($d['pain_points'])))
    <div class="bg-gray-50 rounded-lg p-4 border">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-lg">👤</div>
            <div>
                <p class="font-semibold text-gray-800">{{ $d['name'] }}</p>
                <p class="text-xs text-gray-500">{{ $d['role'] }}</p>
            </div>
        </div>
        @if(isset($d['goals']) && is_array($d['goals']))
            <p class="text-xs font-bold text-green-700 uppercase mb-1">Goals</p>
            <ul class="mb-2 space-y-0.5">
                @foreach($d['goals'] as $goal)
                    <li class="text-xs text-gray-600">• {{ is_string($goal) ? $goal : ($goal['text'] ?? json_encode($goal)) }}</li>
                @endforeach
            </ul>
        @endif
        @if(isset($d['pain_points']) && is_array($d['pain_points']))
            <p class="text-xs font-bold text-red-700 uppercase mb-1">Pain Points</p>
            <ul class="space-y-0.5">
                @foreach($d['pain_points'] as $pain)
                    <li class="text-xs text-gray-600">• {{ is_string($pain) ? $pain : ($pain['text'] ?? json_encode($pain)) }}</li>
                @endforeach
            </ul>
        @endif
    </div>

{{-- VIABILITY SCORE --}}
@elseif(isset($d['score']) && (isset($d['breakdown']) || isset($d['rating']) || isset($d['justification'])))
    <div class="text-center mb-3">
        <span class="text-3xl font-bold text-primary-600">{{ $d['score'] }}</span>
        <span class="text-gray-400 text-sm">/100</span>
    </div>
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

{{-- PRICING CARDS / TIERS --}}
@elseif(isset($d['tiers']) || isset($d['pricing_tiers']))
    @php $tiers = $d['tiers'] ?? $d['pricing_tiers']; @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($tiers as $tier)
            <div class="bg-gray-50 rounded-lg p-4 border text-center">
                <p class="font-semibold text-gray-800">{{ $tier['name'] ?? $tier['title'] ?? '' }}</p>
                <p class="text-xl font-bold text-primary-600 my-2">{{ $tier['price'] ?? '' }}</p>
                @if(isset($tier['features']) && is_array($tier['features']))
                    <ul class="text-xs text-gray-600 space-y-1 text-left mt-3">
                        @foreach($tier['features'] as $feature)
                            <li>✓ {{ is_string($feature) ? $feature : ($feature['name'] ?? json_encode($feature)) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

{{-- FUNDING STRATEGY --}}
@elseif(isset($d['rounds']) || isset($d['funding_rounds']))
    @php $rounds = $d['rounds'] ?? $d['funding_rounds']; @endphp
    <div class="space-y-3">
        @foreach($rounds as $round)
            <div class="bg-gray-50 rounded-lg p-3 border flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $round['name'] ?? $round['round'] ?? '' }}</p>
                    @if(isset($round['timeline']))
                        <p class="text-xs text-gray-500">{{ $round['timeline'] }}</p>
                    @endif
                </div>
                @if(isset($round['target']) || isset($round['amount']))
                    <span class="text-sm font-bold text-primary-600">{{ $round['target'] ?? $round['amount'] }}</span>
                @endif
            </div>
        @endforeach
    </div>

{{-- PROGRESS BARS --}}
@elseif(isset($d['items']) && is_array($d['items']) && (isset($d['items'][0]['percentage']) || isset($d['items'][0]['value'])))
    <div class="space-y-3">
        @foreach($d['items'] as $item)
            @php
                $pct = $item['percentage'] ?? $item['value'] ?? 0;
                $suffix = $item['suffix'] ?? '%';
            @endphp
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-700 font-medium">{{ $item['label'] ?? $item['name'] ?? '' }}</span>
                    <span class="text-gray-500">{{ $pct }}{{ $suffix }}</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-primary-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>

{{-- MILESTONES / PROJECTIONS --}}
@elseif(isset($d['projections']) || isset($d['revenue_milestones']) || isset($d['milestones']))
    @php
        $projections = $d['projections'] ?? $d['highlights'] ?? [];
        $milestones = $d['milestones'] ?? $d['revenue_milestones'] ?? $d['monthly_milestones'] ?? [];
    @endphp
    @if(is_array($projections) && count($projections))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            @foreach($projections as $p)
                <div class="bg-gray-50 rounded-lg p-3 text-center border">
                    <p class="text-xs text-gray-500">{{ $p['label'] ?? $p['title'] ?? '' }}</p>
                    <p class="text-lg font-bold text-gray-800">{{ $p['value'] ?? $p['amount'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    @endif
    @if(is_array($milestones) && count($milestones))
        <div class="space-y-2">
            @foreach($milestones as $m)
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3 border">
                    <span class="text-sm font-medium text-gray-800">{{ $m['month'] ?? $m['period'] ?? $m['title'] ?? '' }}</span>
                    <span class="text-sm font-bold text-primary-600">{{ $m['revenue'] ?? $m['mrr'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    @endif

{{-- GROWTH CHANNELS --}}
@elseif(isset($d['channels']) || isset($d['growth_channels']))
    @php $channels = $d['channels'] ?? $d['growth_channels']; @endphp
    <div class="space-y-2">
        @foreach($channels as $ch)
            <div class="bg-gray-50 rounded-lg p-3 border flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $ch['name'] ?? $ch['title'] ?? $ch['channel'] ?? '' }}</p>
                    @if(isset($ch['description']))
                        <p class="text-xs text-gray-500">{{ $ch['description'] }}</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    @if(isset($ch['new_users_percentage']))
                        <span class="text-xs px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 font-medium">{{ $ch['new_users_percentage'] }}</span>
                    @endif
                    @if(isset($ch['cac']))
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">CAC: {{ $ch['cac'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

{{-- PARTNERSHIPS --}}
@elseif(isset($d['partnerships']) && is_array($d['partnerships']))
    <div class="space-y-2">
        @foreach($d['partnerships'] as $p)
            <div class="bg-gray-50 rounded-lg p-3 border">
                <p class="text-sm font-semibold text-gray-800">{{ $p['name'] ?? $p['partner'] ?? '' }}</p>
                @if(isset($p['type']))
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $p['type'] }}</span>
                @endif
                @if(isset($p['description']))
                    <p class="text-xs text-gray-500 mt-1">{{ $p['description'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

{{-- DIFFERENTIATORS --}}
@elseif(isset($d['differentiators']) && is_array($d['differentiators']))
    <div class="space-y-2">
        @foreach($d['differentiators'] as $diff)
            <div class="bg-gray-50 rounded-lg p-3 border">
                <p class="text-sm font-semibold text-gray-800">{{ $diff['title'] ?? $diff['name'] ?? '' }}</p>
                @if(isset($diff['description']))
                    <p class="text-xs text-gray-500 mt-1">{{ $diff['description'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

{{-- MVP DEFINITION --}}
@elseif(isset($d['features']) && (isset($d['core_concept']) || isset($d['success_criteria']) || isset($d['must_have_features'])))
    @if(isset($d['core_concept']))
        <div class="bg-primary-50 rounded-lg p-3 border border-primary-200 mb-3">
            <p class="text-xs font-bold text-primary-700 uppercase mb-1">Core Concept</p>
            <p class="text-sm text-gray-700">{{ $d['core_concept'] }}</p>
        </div>
    @endif
    @php $features = $d['must_have_features'] ?? $d['features'] ?? []; @endphp
    @if(is_array($features) && count($features))
        <p class="text-xs font-bold text-gray-600 uppercase mb-2">Must-Have Features</p>
        <div class="space-y-1.5">
            @foreach($features as $i => $f)
                <div class="flex items-start gap-2 bg-gray-50 rounded-lg p-2 border text-sm">
                    <span class="bg-primary-100 text-primary-700 text-xs font-bold w-5 h-5 rounded flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                    <span class="text-gray-800">{{ is_string($f) ? $f : ($f['name'] ?? $f['title'] ?? $f['feature'] ?? json_encode($f)) }}</span>
                </div>
            @endforeach
        </div>
    @endif

{{-- TECH ARCHITECTURE --}}
@elseif(isset($d['technology_stack']) || (isset($d['frontend']) && isset($d['backend'])))
    @if(isset($d['technology_stack']) && is_array($d['technology_stack']))
        @foreach($d['technology_stack'] as $layer => $techs)
            <p class="text-xs font-bold text-gray-600 uppercase mb-1 mt-2 first:mt-0">{{ ucwords(str_replace('_', ' ', $layer)) }}</p>
            <div class="flex flex-wrap gap-1.5 mb-2">
                @foreach((is_array($techs) ? $techs : [$techs]) as $tech)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ is_string($tech) ? $tech : ($tech['name'] ?? json_encode($tech)) }}</span>
                @endforeach
            </div>
        @endforeach
    @else
        @foreach(['frontend', 'backend', 'database', 'infrastructure', 'devops'] as $layer)
            @if(isset($d[$layer]))
                <p class="text-xs font-bold text-gray-600 uppercase mb-1 mt-2 first:mt-0">{{ ucfirst($layer) }}</p>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach((is_array($d[$layer]) ? $d[$layer] : [$d[$layer]]) as $tech)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ is_string($tech) ? $tech : ($tech['name'] ?? json_encode($tech)) }}</span>
                    @endforeach
                </div>
            @endif
        @endforeach
    @endif

{{-- DEVELOPMENT ROADMAP --}}
@elseif(isset($d['phases']) && is_array($d['phases']) && isset($d['phases'][0]['timeline']))
    <div class="space-y-3">
        @foreach($d['phases'] as $phase)
            <div class="bg-gray-50 rounded-lg p-3 border">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm font-semibold text-gray-800">{{ $phase['name'] ?? $phase['phase_name'] ?? $phase['title'] ?? '' }}</p>
                    <span class="text-xs text-gray-500">{{ $phase['timeline'] ?? '' }}</span>
                </div>
                @if(isset($phase['description']))
                    <p class="text-xs text-gray-500">{{ $phase['description'] }}</p>
                @endif
                @if(isset($phase['deliverables']) && is_array($phase['deliverables']))
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach($phase['deliverables'] as $del)
                            <span class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700">{{ is_string($del) ? $del : ($del['name'] ?? json_encode($del)) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

{{-- LAUNCH PLAN --}}
@elseif(isset($d['phases']) && is_array($d['phases']))
    <div class="space-y-3">
        @foreach($d['phases'] as $phase)
            <div class="bg-gray-50 rounded-lg p-3 border">
                <p class="text-sm font-semibold text-gray-800 mb-1">{{ $phase['name'] ?? $phase['phase_name'] ?? $phase['title'] ?? '' }}</p>
                @php $tasks = $phase['tasks'] ?? $phase['activities'] ?? []; @endphp
                @if(is_array($tasks) && count($tasks))
                    <ul class="space-y-0.5 mt-1">
                        @foreach($tasks as $task)
                            <li class="text-xs text-gray-600">• {{ is_string($task) ? $task : ($task['name'] ?? $task['title'] ?? json_encode($task)) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

{{-- PESTEL --}}
@elseif(isset($d['categories']) || isset($d['political']))
    @php
        $categories = $d['categories'] ?? [];
        if (empty($categories)) {
            foreach (['political', 'economic', 'social', 'technological', 'environmental', 'legal'] as $cat) {
                if (isset($d[$cat])) $categories[] = ['name' => ucfirst($cat), 'factors' => is_array($d[$cat]) ? $d[$cat] : [$d[$cat]]];
            }
        }
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @foreach($categories as $cat)
            <div class="bg-gray-50 rounded-lg p-3 border">
                <p class="text-xs font-bold text-gray-600 uppercase mb-1.5">{{ $cat['name'] ?? '' }}</p>
                @if(isset($cat['factors']) && is_array($cat['factors']))
                    <ul class="space-y-0.5">
                        @foreach($cat['factors'] as $factor)
                            <li class="text-xs text-gray-600">• {{ is_string($factor) ? $factor : ($factor['text'] ?? $factor['description'] ?? json_encode($factor)) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

{{-- FUNNEL CHART --}}
@elseif(isset($d['stages']) && is_array($d['stages']) && isset($d['stages'][0]['value']))
    <div class="space-y-2">
        @foreach($d['stages'] as $i => $stage)
            @php $width = isset($stage['percentage']) ? (float)str_replace('%', '', $stage['percentage']) : (100 - ($i * 15)); @endphp
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <div class="bg-primary-100 rounded py-2 px-3 text-center" style="width: {{ max($width, 20) }}%; margin: 0 auto;">
                        <span class="text-xs font-medium text-primary-800">{{ $stage['name'] ?? $stage['title'] ?? '' }}</span>
                    </div>
                </div>
                <span class="text-xs font-bold text-gray-700 min-w-[50px] text-right">{{ $stage['value'] ?? '' }}</span>
            </div>
        @endforeach
    </div>

{{-- STRUCTURED TEXT (title + sections with heading/content) --}}
@elseif(isset($d['title']) && isset($d['sections']) && is_array($d['sections']))
    <div class="space-y-3">
        <p class="text-sm font-semibold text-gray-800">{{ $d['title'] }}</p>
        @foreach($d['sections'] as $sec)
            <div class="bg-gray-50 rounded-lg p-3 border">
                @if(isset($sec['heading']) || isset($sec['title']))
                    <p class="text-xs font-bold text-gray-600 uppercase mb-1.5">{{ $sec['heading'] ?? $sec['title'] ?? '' }}</p>
                @endif
                @if(isset($sec['content']))
                    @if(is_array($sec['content']))
                        <ul class="space-y-0.5">
                            @foreach($sec['content'] as $item)
                                <li class="text-xs text-gray-700">• {{ is_string($item) ? $item : ($item['text'] ?? $item['title'] ?? json_encode($item, JSON_UNESCAPED_UNICODE)) }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs text-gray-700">{{ $sec['content'] }}</p>
                    @endif
                @elseif(isset($sec['body']))
                    <p class="text-xs text-gray-700">{{ $sec['body'] }}</p>
                @elseif(isset($sec['text']))
                    <p class="text-xs text-gray-700">{{ $sec['text'] }}</p>
                @endif
                @if(isset($sec['items']) && is_array($sec['items']))
                    <ul class="space-y-0.5 mt-1">
                        @foreach($sec['items'] as $item)
                            <li class="text-xs text-gray-700">• {{ is_string($item) ? $item : ($item['text'] ?? $item['title'] ?? $item['name'] ?? json_encode($item, JSON_UNESCAPED_UNICODE)) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

{{-- FALLBACK: Render as formatted key-value pairs --}}
@else
    @if(is_array($d))
        <div class="space-y-2">
            @foreach($d as $key => $value)
                <div class="text-sm">
                    <span class="font-medium text-gray-600">{{ ucwords(str_replace(['_', '-'], ' ', $key)) }}:</span>
                    @if(is_array($value))
                        <div class="ml-4 mt-1">
                            @if(array_is_list($value))
                                <ul class="space-y-0.5">
                                    @foreach($value as $item)
                                        <li class="text-xs text-gray-700">• {{ is_string($item) ? $item : json_encode($item, JSON_UNESCAPED_UNICODE) }}</li>
                                    @endforeach
                                </ul>
                            @else
                                @foreach($value as $subKey => $subVal)
                                    <p class="text-xs text-gray-700"><span class="font-medium">{{ ucwords(str_replace(['_', '-'], ' ', $subKey)) }}:</span> {{ is_string($subVal) ? $subVal : json_encode($subVal, JSON_UNESCAPED_UNICODE) }}</p>
                                @endforeach
                            @endif
                        </div>
                    @else
                        <span class="text-gray-800"> {{ $value }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-700">{{ $d }}</p>
    @endif
@endif
