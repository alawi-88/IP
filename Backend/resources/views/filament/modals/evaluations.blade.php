<div class="space-y-4">
    <table class="w-full border-collapse border border-gray-300">
        <thead>
        <tr class="bg-black text-white">
            <th class="border border-gray-300 px-4 py-2">Question</th>
            <th class="border border-gray-300 px-4 py-2">Answer</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($evaluations as $evaluation)
            @php
                $weight = $evaluation->weight ?? 0;
                $isIncluded = $weight > 0;

                // Get translated label for main criterion
                $criterionLabel = ucwords(str_replace('_', ' ', $evaluation->question));
                if ($form && isset($form->evaluation_config['evaluation_criteria'])) {
                    foreach ($form->evaluation_config['evaluation_criteria'] as $criterion) {
                        if (isset($criterion['slug']) && $criterion['slug'] === $evaluation->question) {
                            if (isset($criterion['label'])) {
                                if (is_array($criterion['label'])) {
                                    $currentLang = app()->getLocale();
                                    $criterionLabel = $criterion['label'][$currentLang] ?? $criterion['label']['en'] ?? $criterion['label']['ar'] ?? $criterionLabel;
                                } else {
                                    $criterionLabel = $criterion['label'];
                                }
                            }
                            break;
                        }
                    }
                }
            @endphp
            <tr>
                <td class="border border-gray-300 px-4 py-2">
                    {{ $criterionLabel }}
                    @if (!$isIncluded)
                        <span class="ml-2 text-red-600 font-semibold">(Not Included)</span>
                    @endif
                </td>
                <td class="border border-gray-300 px-4 py-2">{{ $evaluation->answer }}</td>
            </tr>

            {{-- Show weight --}}
            <tr>
                <td class="border border-gray-300 px-4 py-2">Weight</td>
                <td class="border border-gray-300 px-4 py-2">{{ $weight }}%</td>
            </tr>

            @php
                $details = json_decode($evaluation->details, true);
            @endphp

            @if(is_array($details))
                @foreach ($details as $key => $value)
                    @php
                        // Check if this is a comment field
                        $isComment = str_ends_with($key, '_comment');
                        $fullSlug = $isComment ? str_replace('_comment', '', $key) : $key;
                        // Normalize slug: strip parent criterion prefix so it matches form config (e.g. "product_innovation_value_proposition" -> "value_proposition")
                        $slug = $fullSlug;
                        if (!empty($evaluation->question) && str_starts_with($fullSlug, $evaluation->question . '_')) {
                            $slug = substr($fullSlug, strlen($evaluation->question . '_'));
                        }

                        // Get subcriterion label and weight from form configuration (only for current main criterion)
                        $subcriterionLabel = ucwords(str_replace('_', ' ', $slug));
                        $subcriterionWeight = 0;
                        if ($form && isset($form->evaluation_config['evaluation_criteria'])) {
                            foreach ($form->evaluation_config['evaluation_criteria'] as $criterion) {
                                if (isset($criterion['slug']) && $criterion['slug'] !== $evaluation->question) {
                                    continue;
                                }
                                if (isset($criterion['subcriteria'])) {
                                    foreach ($criterion['subcriteria'] as $subcriterion) {
                                        if (isset($subcriterion['slug']) && $subcriterion['slug'] === $slug) {
                                            $subcriterionWeight = (float) ($subcriterion['weight'] ?? 0);
                                            // Get translated label
                                            if (isset($subcriterion['label'])) {
                                                if (is_array($subcriterion['label'])) {
                                                    $currentLang = app()->getLocale();
                                                    $subcriterionLabel = $subcriterion['label'][$currentLang] ?? $subcriterion['label']['en'] ?? $subcriterion['label']['ar'] ?? $subcriterionLabel;
                                                } else {
                                                    $subcriterionLabel = $subcriterion['label'];
                                                }
                                            }
                                            break 2;
                                        }
                                    }
                                }
                                break;
                            }
                        }

                        $isSubcriterionIncluded = $isIncluded && $subcriterionWeight > 0;
                    @endphp

                    @if (!$isComment)
                        <tr>
                            <td class="border border-gray-300 px-4 py-2 text-green-700">
                                {{ $subcriterionLabel }}
                                @if (!$isSubcriterionIncluded)
                                    <span class="ml-2 text-red-600 font-semibold">(Not Included)</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-4 py-2">
                                @if(is_array($value))
                                    <strong>Answer:</strong> {{ is_numeric($value['answer'] ?? null) ? number_format($value['answer'], 2, '.', '') : ($value['answer'] ?? '-') }} <br>
                                    @if (!empty($value['comment']))
                                        <strong>Comment:</strong> <em>{{ $value['comment'] }}</em>
                                    @endif
                                @else
                                    {{ is_numeric($value) ? number_format($value, 2, '.', '') : $value }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            @endif

            @if($evaluation->comment)
                <tr>
                    <td class="border border-gray-300 px-4 py-2 font-semibold text-blue-800">Comment</td>
                    <td class="border border-gray-300 px-4 py-2 italic">{{ $evaluation->comment }}</td>
                </tr>
            @endif

            {{-- Space between each question --}}
            <tr><td colspan="2" class="py-2"></td></tr>
        @endforeach

        {{-- ✅ Final Score (from form_evaluation_scores) --}}
        @php
            $evaluationScore = $formScore?->evaluation_score;
        @endphp

        @if($evaluationScore)
            <tr>
                <td class="border border-gray-300 px-4 py-2 font-semibold text-blue-800">
                    Total Score ({{ $form->name ?? 'Form' }})
                </td>
                <td class="border border-gray-300 px-4 py-2 italic">
                    {{ number_format($evaluationScore, 2) }} %
                </td>
            </tr>
        @endif

        @if($formScore?->final_comment)
            <tr>
                <td class="border border-gray-300 px-4 py-2 font-semibold text-blue-800">
                    Final Comment
                </td>
                <td class="border border-gray-300 px-4 py-2 italic">
                    {{ $formScore->final_comment }}
                </td>
            </tr>
        @endif
        </tbody>
    </table>
</div>
