@if ($current)
    <div class="flex items-center  gap-3 space-x-6">
        <div class="flex items-center space-x-4 gap-3">
            <div class="flex items-center space-x-2 text-yellow-400 text-xl font-semibold">
                <x-heroicon-m-trophy class="w-6 h-6" />
                <span class="ps-2">{{ $current->title }}</span>
            </div>

            <div class="flex items-center space-x-4 gap-3">
                <div class="text-sm text-gray-300 flex items-center space-x-2">
                    <span class="text-xs">Status:</span>
                    <span class="inline-flex items-center ps-2 rounded-full text-xs font-semibold
                        {{ $current->is_published ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200' }}">
                        {{ $current->is_published ? 'Published' : 'Unpublished' }}
                    </span>
                </div>

                <div class="text-sm text-gray-300 flex items-center space-x-2 border-l border-gray-700 pl-4">
                    <span class="text-xs">Current stage:</span>
                    <span class="inline-flex items-center ps-2 rounded-full text-xs font-semibold
                        {{ $currentStage ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200' }}">
                        {{ $stageTitle ?? 'No active stage' }}
                    </span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('filament.competition.switch') }}" class="w-64">
            @csrf
            <label for="competition-select" class="sr-only">Switch Program</label>
            <select
                id="competition-select"
                name="competition_id"
                onchange="this.form.submit()"
                class="w-full bg-gray-800 text-white border border-gray-700 rounded-md
           px-2 py-1 text-xs shadow-sm
           focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400
           transition duration-150 ease-in-out cursor-pointer appearance-none"
                style="background-color: black;"
            >
            <option disabled selected class="text-gray-400 bg-gray-800"> Switch Program</option>
            @foreach ($competitions as $comp)
                <option value="{{ $comp->id }}" class="bg-gray-800 text-dark">
                    {{ $comp->title }}
                </option>
                @endforeach
                </select>
        </form>
    </div>
@endif
