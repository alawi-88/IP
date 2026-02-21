<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div x-data="{ activeTab: @entangle('activeTab') }" class="space-y-6">
        {{-- Tab Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <nav style="display:flex;overflow-x:auto;-webkit-overflow-scrolling:touch;border-bottom:2px solid #e5e7eb;" aria-label="Tabs">
                @php
                    $tabs = [
                        'setup' => ['label' => 'Setup', 'icon' => 'heroicon-o-cog-6-tooth'],
                        'registration' => ['label' => 'Registration & Teams', 'icon' => 'heroicon-o-clipboard-document-list'],
                        'forms' => ['label' => 'Forms & Submissions', 'icon' => 'heroicon-o-document-text'],
                        'scoring' => ['label' => 'Scoring & Evaluation', 'icon' => 'heroicon-o-sparkles'],
                        'people' => ['label' => 'People & Content', 'icon' => 'heroicon-o-users'],
                        'labels' => ['label' => 'Labels', 'icon' => 'heroicon-o-language'],
                    ];
                @endphp

                @foreach ($tabs as $key => $tab)
                    <button
                        wire:click="switchTab('{{ $key }}')"
                        x-on:click="activeTab = '{{ $key }}'"
                    :style="activeTab === '{{ $key }}' ? 'flex-shrink:0;display:inline-flex;align-items:center;gap:0.5rem;white-space:nowrap;border-bottom:3px solid #25935F;padding:0.875rem 1.25rem;font-size:0.875rem;font-weight:600;color:#25935F;cursor:pointer;margin-bottom:-2px;background:none;' : 'flex-shrink:0;display:inline-flex;align-items:center;gap:0.5rem;white-space:nowrap;border-bottom:3px solid transparent;padding:0.875rem 1.25rem;font-size:0.875rem;font-weight:500;color:#6b7280;cursor:pointer;margin-bottom:-2px;background:none;'"
                        
                    >
                        <x-dynamic-component :component="$tab['icon']" class="h-4 w-4" />
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Archived Banner --}}
        @if ($isArchived)
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 flex items-center gap-3">
                <x-heroicon-o-archive-box class="h-5 w-5 text-amber-500" />
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    This program is <strong>archived</strong>. Editing is disabled. You can restore it from the Programs list.
                </p>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB 1: SETUP (Overview + Stages & Tracks) --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'setup'" x-cloak>
            {{-- Overview Form --}}
            <form wire:submit="saveOverview">
                {{ $this->overviewForm }}

                @unless ($isArchived)
                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-check">
                            Save Program Details
                        </x-filament::button>
                    </div>
                @endunless
            </form>

            {{-- Stages & Tracks (read-only cards) --}}
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                    <x-heroicon-o-bars-3-bottom-left class="h-5 w-5 text-primary-500" />
                    Stages & Tracks
                </h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Stages --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            Program Stages
                        </h4>
                        @if ($stages->isEmpty())
                            <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-bars-3-bottom-left class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p class="text-sm">No stages configured yet.</p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($stages as $stage)
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-100 dark:border-gray-600">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-white text-sm">
                                                    {{ $stage->getTranslation('title', 'en') }}
                                                </h5>
                                                @if ($stage->starts_at || $stage->ends_at)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                        {{ $stage->starts_at?->format('M d, Y') }} — {{ $stage->ends_at?->format('M d, Y') }}
                                                    </p>
                                                @endif
                                            </div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                {{ $stage->is_visible ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-600/30 dark:text-gray-400' }}">
                                                {{ $stage->is_visible ? 'Visible' : 'Hidden' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Tracks --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            Tracks & Sub-Tracks
                        </h4>
                        @if ($tracks->isEmpty())
                            <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-rectangle-stack class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p class="text-sm">No tracks configured yet.</p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($tracks as $track)
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-100 dark:border-gray-600">
                                        <h5 class="font-medium text-gray-900 dark:text-white text-sm">
                                            {{ is_array($track->name) ? ($track->name['en'] ?? '') : $track->name }}
                                        </h5>
                                        @if ($track->subTracks->isNotEmpty())
                                            <div class="mt-1.5 flex flex-wrap gap-1">
                                                @foreach ($track->subTracks as $sub)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                                        {{ is_array($sub->name) ? ($sub->name['en'] ?? '') : $sub->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB 2: REGISTRATION & TEAMS --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'registration'" x-cloak>
            {{-- Registration Config --}}
            <form wire:submit="saveRegistration">
                {{ $this->registrationForm }}

                @unless ($isArchived)
                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-check">
                            Save Registration Config
                        </x-filament::button>
                    </div>
                @endunless
            </form>

            {{-- Team Config --}}
            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                <form wire:submit="saveTeam">
                    {{ $this->teamForm }}

                    @unless ($isArchived)
                        <div class="mt-6 flex justify-end">
                            <x-filament::button type="submit" icon="heroicon-o-check">
                                Save Team Config
                            </x-filament::button>
                        </div>
                    @endunless
                </form>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB 3: FORMS & SUBMISSIONS --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'forms'" x-cloak>
            {{-- Forms Builder --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-document-duplicate class="h-5 w-5 text-primary-500" />
                        Program Forms
                    </h3>
                    <a href="{{ \App\Filament\Resources\FormResource::getUrl('create') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
                        <x-heroicon-o-plus class="h-4 w-4" />
                        Create New Form
                    </a>
                </div>

                @if ($formsList->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-document-duplicate class="h-10 w-10 mx-auto mb-2 opacity-50" />
                        <p class="font-medium">No forms created yet</p>
                        <p class="text-sm mt-1">Create registration, project submission, and evaluation forms.</p>
                    </div>
                @else
                    @php
                        $grouped = $formsList->groupBy('type');
                        $typeLabels = [
                            'registration' => ['label' => 'Registration', 'color' => 'blue', 'icon' => 'heroicon-o-clipboard-document-list'],
                            'project-submission' => ['label' => 'Project Submission', 'color' => 'green', 'icon' => 'heroicon-o-document-text'],
                            'evaluation' => ['label' => 'Evaluation', 'color' => 'amber', 'icon' => 'heroicon-o-star'],
                        ];
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($formsList as $formItem)
                            @php
                                $formName = is_array($formItem->name)
                                    ? ($formItem->name['en'] ?? reset($formItem->name))
                                    : $formItem->name;
                                $typeMeta = $typeLabels[$formItem->type] ?? ['label' => ucfirst($formItem->type), 'color' => 'gray', 'icon' => 'heroicon-o-document'];
                            @endphp
                            <a href="{{ \App\Filament\Resources\FormResource::getUrl('edit', ['record' => $formItem->id]) }}"
                               class="block bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-100 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-600 transition-colors group">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h5 class="font-medium text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 text-sm">
                                            {{ $formName ?: 'Untitled Form #' . $formItem->id }}
                                        </h5>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs px-1.5 py-0.5 rounded bg-{{ $typeMeta['color'] }}-100 text-{{ $typeMeta['color'] }}-800 dark:bg-{{ $typeMeta['color'] }}-900/30 dark:text-{{ $typeMeta['color'] }}-300">
                                                {{ $typeMeta['label'] }}
                                            </span>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                                {{ $formItem->is_active ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-600/30 dark:text-gray-400' }}">
                                                {{ $formItem->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <x-heroicon-o-pencil class="h-4 w-4 text-gray-400 group-hover:text-primary-500 flex-shrink-0 ml-2" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Submission & Evaluation Config --}}
            <form wire:submit="saveSubmissionEval">
                {{ $this->submissionEvalForm }}

                @unless ($isArchived)
                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-check">
                            Save Submission & Evaluation Config
                        </x-filament::button>
                    </div>
                @endunless
            </form>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB 4: SCORING & EVALUATION --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'scoring'" x-cloak>
            {{-- AI Scoring --}}
            <form wire:submit="saveAiScoring">
                {{ $this->aiScoringForm }}

                @unless ($isArchived)
                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-check">
                            Save AI Scoring Config
                        </x-filament::button>
                    </div>
                @endunless
            </form>

            {{-- Registration Evaluation --}}
            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                <form wire:submit="saveRegEval">
                    {{ $this->regEvalForm }}

                    @unless ($isArchived)
                        <div class="mt-6 flex justify-end">
                            <x-filament::button type="submit" icon="heroicon-o-check">
                                Save Registration Evaluation
                            </x-filament::button>
                        </div>
                    @endunless
                </form>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB 5: PEOPLE & CONTENT --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'people'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Mentors --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <x-heroicon-o-academic-cap class="h-5 w-5 text-primary-500" />
                        Mentors
                        @if ($mentors instanceof \Illuminate\Support\Collection || is_countable($mentors))
                            <span class="ml-1 text-sm font-normal text-gray-500">({{ count($mentors) }})</span>
                        @endif
                    </h3>

                    @if (($mentors instanceof \Illuminate\Support\Collection && $mentors->isEmpty()) || empty($mentors))
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-academic-cap class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p class="text-sm">No mentors assigned.</p>
                        </div>
                    @else
                        <div class="space-y-1">
                            @foreach ($mentors as $mentor)
                                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                                            {{ strtoupper(substr($mentor->name ?? 'M', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $mentor->name ?? 'Unknown' }}</p>
                                        @if ($mentor->email ?? false)
                                            <p class="text-xs text-gray-500 truncate">{{ $mentor->email }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Judges --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <x-heroicon-o-scale class="h-5 w-5 text-primary-500" />
                        Judges
                        @if ($judges instanceof \Illuminate\Support\Collection || is_countable($judges))
                            <span class="ml-1 text-sm font-normal text-gray-500">({{ count($judges) }})</span>
                        @endif
                    </h3>

                    @if (($judges instanceof \Illuminate\Support\Collection && $judges->isEmpty()) || empty($judges))
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-scale class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p class="text-sm">No judges assigned.</p>
                        </div>
                    @else
                        <div class="space-y-1">
                            @foreach ($judges as $judge)
                                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <div class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-medium text-amber-700 dark:text-amber-300">
                                            {{ strtoupper(substr($judge->name ?? 'J', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $judge->name ?? 'Unknown' }}</p>
                                        @if ($judge->email ?? false)
                                            <p class="text-xs text-gray-500 truncate">{{ $judge->email }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Events --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <x-heroicon-o-calendar-days class="h-5 w-5 text-primary-500" />
                        Events
                        @if ($events instanceof \Illuminate\Support\Collection || is_countable($events))
                            <span class="ml-1 text-sm font-normal text-gray-500">({{ count($events) }})</span>
                        @endif
                    </h3>

                    @if (($events instanceof \Illuminate\Support\Collection && $events->isEmpty()) || empty($events))
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-calendar-days class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p class="text-sm">No events scheduled.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($events as $event)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-100 dark:border-gray-600">
                                    <h5 class="font-medium text-gray-900 dark:text-white text-sm">
                                        {{ is_array($event->title) ? ($event->title['en'] ?? '') : ($event->title ?? 'Untitled') }}
                                    </h5>
                                    @if ($event->starts_at ?? false)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $event->starts_at?->format('M d, Y H:i') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Guidelines --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <x-heroicon-o-book-open class="h-5 w-5 text-primary-500" />
                        Guidelines
                        @if ($guidelines instanceof \Illuminate\Support\Collection || is_countable($guidelines))
                            <span class="ml-1 text-sm font-normal text-gray-500">({{ count($guidelines) }})</span>
                        @endif
                    </h3>

                    @if (($guidelines instanceof \Illuminate\Support\Collection && $guidelines->isEmpty()) || empty($guidelines))
                        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-book-open class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p class="text-sm">No guidelines added.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($guidelines as $guideline)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-100 dark:border-gray-600">
                                    <h5 class="font-medium text-gray-900 dark:text-white text-sm">
                                        {{ is_array($guideline->title) ? ($guideline->title['en'] ?? '') : ($guideline->title ?? 'Untitled') }}
                                    </h5>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- TAB 6: LABELS --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'labels'" x-cloak>
            <div x-data="{ labelsOpen: true }">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button
                        type="button"
                        x-on:click="labelsOpen = !labelsOpen"
                        class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-language class="h-5 w-5 text-primary-500" />
                            Bilingual Labels
                        </h3>
                        <x-heroicon-o-chevron-down
                            class="h-5 w-5 text-gray-400 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': labelsOpen }"
                        />
                    </button>

                    <div x-show="labelsOpen" x-collapse>
                        <div class="px-6 pb-6 border-t border-gray-100 dark:border-gray-700 pt-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Customize the bilingual labels (English & Arabic) used across this program's participant-facing pages.
                            </p>
                            <form wire:submit="saveLabels">
                                {{ $this->labelsForm }}

                                @unless ($isArchived)
                                    <div class="mt-6 flex justify-end">
                                        <x-filament::button type="submit" icon="heroicon-o-check">
                                            Save Labels
                                        </x-filament::button>
                                    </div>
                                @endunless
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chatbot Widget --}}
    @if (class_exists(\App\Livewire\AdminChatbot::class))
        @livewire('admin-chatbot', ['programId' => $record->id, 'activeTab' => $activeTab])
    @endif
</x-filament-panels::page>
