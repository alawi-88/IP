<div class="space-y-6 p-4">
    @if(empty($breakdown))
        <p class="text-gray-500">No evaluation data available.</p>
    @else
        {{-- Overall Summary --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-2">Overall Score / الدرجة الإجمالية</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Final Score</p>
                    <p class="text-2xl font-bold text-primary-600">{{ $breakdown['final_score'] ?? 0 }}</p>
                </div>
                @if($breakdown['minimum_threshold'] !== null)
                <div>
                    <p class="text-sm text-gray-500">Min. Threshold</p>
                    <p class="text-2xl font-bold">{{ $breakdown['minimum_threshold'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    @if($breakdown['meets_threshold'])
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            ✓ Meets Threshold
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            ✗ Below Threshold
                        </span>
                    @endif
                </div>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-2">Evaluators: {{ $breakdown['evaluator_count'] ?? 0 }}</p>
        </div>

        {{-- Per-Form Breakdown --}}
        @foreach($breakdown['forms'] ?? [] as $form)
        <div class="border rounded-lg overflow-hidden">
            <div class="bg-primary-50 dark:bg-primary-900/20 px-4 py-2">
                <h4 class="font-semibold">{{ $form['form_name'] }}</h4>
                @if($form['dimension'])
                    <span class="text-sm text-gray-500">{{ $form['dimension'] }}</span>
                @endif
                <span class="text-sm text-gray-500 ml-2">(Max: {{ $form['max_possible_score'] }})</span>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-gray-50 dark:bg-gray-800">
                        <th class="text-left px-4 py-2">Criterion</th>
                        <th class="text-center px-2 py-2">Max</th>
                        <th class="text-center px-2 py-2">Weight</th>
                        @foreach($form['criteria'][0]['evaluator_scores'] ?? [] as $evScore)
                            <th class="text-center px-2 py-2">{{ \Str::limit($evScore['evaluator_name'], 12) }}</th>
                        @endforeach
                        <th class="text-center px-2 py-2 font-bold">Average</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($form['criteria'] as $criterion)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $criterion['name'] }}</td>
                        <td class="text-center px-2 py-2">{{ $criterion['max_score'] }}</td>
                        <td class="text-center px-2 py-2">{{ $criterion['weight'] }}%</td>
                        @foreach($criterion['evaluator_scores'] as $evScore)
                            <td class="text-center px-2 py-2">
                                @if($evScore['score'] !== null)
                                    <span class="@if($evScore['score'] >= $criterion['max_score'] * 0.7) text-green-600 @elseif($evScore['score'] >= $criterion['max_score'] * 0.4) text-yellow-600 @else text-red-600 @endif font-medium">
                                        {{ $evScore['score'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center px-2 py-2 font-bold">{{ $criterion['average_score'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    @endif
</div>
