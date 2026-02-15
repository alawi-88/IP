<div class="space-y-4">
    <table class="w-full border-collapse border border-gray-300">
        <thead>
        <tr class="bg-gray-100">
            <th class="border border-gray-300 px-4 py-2">Question</th>
            <th class="border border-gray-300 px-4 py-2">Answer</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($satisfactions as $satisfaction)
            @if($satisfaction->question != 'interested_attending_similar_programs')
            <tr>
                <td class="border border-gray-300 px-4 py-2">{{ __('satisfaction.'.$satisfaction->question) }}</td>
                <td class="border border-gray-300 px-4 py-2">{{ $satisfaction->answer }}</td>
            </tr>
            @endif

            @if($satisfaction->question == 'interested_attending_similar_programs')
                <tr>
                    <td class="border border-gray-300 px-4 py-2"> {{ __('satisfaction.'.$satisfaction->question) }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $satisfaction->answer == 1 ? 'Yes, Interested' : 'No, Not interested' }}</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
</div>
