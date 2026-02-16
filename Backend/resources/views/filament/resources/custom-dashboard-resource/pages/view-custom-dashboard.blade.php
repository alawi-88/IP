<x-filament-panels::page>
    {{-- Runtime Filters --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('dashboard.filters') }}
            </h3>
            <x-filament::button color="gray" size="sm" wire:click="resetFilters">
                {{ __('Reset') }}
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('dashboard.filter_by_competition') }}
                </label>
                <select wire:model.live="filterCompetition"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                    <option value="">{{ __('dashboard.all') }}</option>
                    @foreach($this->getCompetitionOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('dashboard.filter_by_status') }}
                </label>
                <select wire:model.live="filterStatus"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                    <option value="">{{ __('dashboard.all') }}</option>
                    @foreach($this->getStatusOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('dashboard.date_from') }}
                </label>
                <input type="date" wire:model.live="filterDateFrom"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('dashboard.date_to') }}
                </label>
                <input type="date" wire:model.live="filterDateTo"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <x-filament::button wire:click="applyFilters" color="primary" size="sm">
                {{ __('Apply Filters') }}
            </x-filament::button>
        </div>
    </div>

    {{-- Loading State --}}
    @if($isLoading)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @for($i = 0; $i < 3; $i++)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 animate-pulse">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-4"></div>
                    <div class="h-48 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            @endfor
        </div>
    @endif

    {{-- Error State --}}
    @if($errorMessage)
        <div class="fi-section rounded-xl bg-red-50 dark:bg-red-900/20 shadow-sm ring-1 ring-red-200 dark:ring-red-800 p-6 text-center">
            <x-heroicon-o-exclamation-circle class="w-12 h-12 text-red-500 mx-auto mb-3" />
            <p class="text-red-700 dark:text-red-300 font-medium">{{ $errorMessage }}</p>
            <x-filament::button wire:click="loadDashboardData" color="danger" size="sm" class="mt-3">
                {{ __('dashboard.retry') }}
            </x-filament::button>
        </div>
    @endif

    {{-- Widget Grid --}}
    @if(!$isLoading && !$errorMessage)
        @if(count($widgetData) === 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center">
                <x-heroicon-o-chart-bar-square class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <p class="text-gray-500 dark:text-gray-400 text-lg">{{ __('dashboard.no_data') }}</p>
            </div>
        @else
            {{-- KPI Cards Row --}}
            @php
                $kpiWidgets = collect($widgetData)->where('visualization_type', 'kpi');
                $chartWidgets = collect($widgetData)->where('visualization_type', '!=', 'kpi')->where('visualization_type', '!=', 'table');
                $tableWidgets = collect($widgetData)->where('visualization_type', 'table');
            @endphp

            @if($kpiWidgets->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ min($kpiWidgets->count(), 4) }} gap-4 mb-6">
                    @foreach($kpiWidgets as $widget)
                        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        {{ $widget['label'] }}
                                    </p>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                                        {{ number_format($widget['data']['value'] ?? 0, ($widget['data']['value'] == intval($widget['data']['value'] ?? 0)) ? 0 : 2) }}
                                        @if(!empty($widget['data']['is_percentage']))
                                            %
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ ucfirst($widget['aggregation_type']) }}
                                        &middot;
                                        {{ $widget['data']['count'] ?? 0 }} {{ __('records') }}
                                    </p>
                                </div>
                                <div class="rounded-full bg-primary-100 dark:bg-primary-900/30 p-3">
                                    @switch($widget['aggregation_type'])
                                        @case('sum')
                                            <x-heroicon-o-calculator class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                            @break
                                        @case('average')
                                            <x-heroicon-o-chart-bar class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                            @break
                                        @case('count')
                                            <x-heroicon-o-hashtag class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                            @break
                                        @default
                                            <x-heroicon-o-presentation-chart-line class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Charts Grid --}}
            @if($chartWidgets->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    @foreach($chartWidgets as $index => $widget)
                        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                                {{ $widget['label'] }}
                                <span class="text-xs font-normal text-gray-400 ml-1">
                                    ({{ ucfirst($widget['aggregation_type']) }})
                                </span>
                            </h4>

                            @if(empty($widget['data']['labels']) || count($widget['data']['labels']) === 0)
                                <div class="flex items-center justify-center h-48 text-gray-400">
                                    <p>{{ __('dashboard.no_data') }}</p>
                                </div>
                            @else
                                <div
                                    x-data="{
                                        chart: null,
                                        init() {
                                            this.renderChart();
                                        },
                                        renderChart() {
                                            const options = this.getChartOptions();
                                            this.chart = new ApexCharts(this.$refs.chartContainer, options);
                                            this.chart.render();
                                        },
                                        getChartOptions() {
                                            const type = '{{ $widget['visualization_type'] }}';
                                            const labels = @js($widget['data']['labels']);
                                            const series = @js($widget['data']['series']);
                                            const isPercentage = {{ !empty($widget['data']['is_percentage']) ? 'true' : 'false' }};

                                            const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#a855f7'];

                                            if (type === 'pie') {
                                                return {
                                                    chart: { type: 'pie', height: 300 },
                                                    labels: labels,
                                                    series: series,
                                                    colors: colors,
                                                    legend: { position: 'bottom' },
                                                    tooltip: {
                                                        y: {
                                                            formatter: function(val) {
                                                                return isPercentage ? val + '%' : val;
                                                            }
                                                        }
                                                    },
                                                    responsive: [{ breakpoint: 480, options: { chart: { width: 280 }, legend: { position: 'bottom' } } }]
                                                };
                                            }

                                            if (type === 'line') {
                                                return {
                                                    chart: { type: 'line', height: 300, toolbar: { show: true } },
                                                    series: [{ name: '{{ $widget['label'] }}', data: series }],
                                                    xaxis: { categories: labels },
                                                    colors: colors,
                                                    stroke: { curve: 'smooth', width: 3 },
                                                    markers: { size: 4 },
                                                    tooltip: {
                                                        y: {
                                                            formatter: function(val) {
                                                                return isPercentage ? val + '%' : val;
                                                            }
                                                        }
                                                    }
                                                };
                                            }

                                            // Default: bar chart
                                            return {
                                                chart: { type: 'bar', height: 300, toolbar: { show: true } },
                                                series: [{ name: '{{ $widget['label'] }}', data: series }],
                                                xaxis: { categories: labels },
                                                colors: colors,
                                                plotOptions: {
                                                    bar: { borderRadius: 6, columnWidth: '60%', distributed: true }
                                                },
                                                legend: { show: false },
                                                tooltip: {
                                                    y: {
                                                        formatter: function(val) {
                                                            return isPercentage ? val + '%' : val;
                                                        }
                                                    }
                                                }
                                            };
                                        }
                                    }"
                                    wire:key="chart-{{ $widget['widget_id'] }}-{{ md5(json_encode($widget['data'])) }}"
                                >
                                    <div x-ref="chartContainer"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Table Widgets --}}
            @if($tableWidgets->count() > 0)
                @foreach($tableWidgets as $widget)
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                            {{ $widget['label'] }}
                            <span class="text-xs font-normal text-gray-400 ml-1">
                                ({{ $widget['data']['count'] ?? 0 }} {{ __('records') }})
                            </span>
                        </h4>

                        @if(empty($widget['data']['labels']) || count($widget['data']['labels']) === 0)
                            <p class="text-gray-400 text-center py-8">{{ __('dashboard.no_data') }}</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('Value') }}
                                            </th>
                                            <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('Count') }}
                                            </th>
                                            @if(!empty($widget['data']['is_percentage']))
                                                <th class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('Percentage') }}
                                                </th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($widget['data']['labels'] as $i => $label)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $label }}
                                                </td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                    {{ $widget['data']['series'][$i] ?? 0 }}
                                                </td>
                                                @if(!empty($widget['data']['is_percentage']))
                                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                                        {{ $widget['data']['series'][$i] ?? 0 }}%
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        @endif
    @endif

</x-filament-panels::page>
