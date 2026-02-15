<div class="space-y-4">
    @forelse($comments as $comment)
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-2">
            <div class="flex justify-between items-start">
                <div>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $comment->admin->name }}</span>
                    <span
                        class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $comment->created_at?->diffForHumans() }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span
                        class="px-2 py-1 text-xs rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                        {{ $comment->getTypeLabel() }}
                    </span>
                    @if ($comment->canDelete())
                        <button wire:click="deleteComment({{ $comment->id }})"
                            class="text-danger-600 hover:text-danger-800 dark:text-danger-400 dark:hover:text-danger-300">
                            <x-heroicon-o-trash class="w-4 h-4" />
                        </button>
                    @endif
                </div>
            </div>
            <p class="text-gray-800 dark:text-gray-200">{{ $comment->content }}</p>
        </div>
    @empty
        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No comments yet</p>
    @endforelse
</div>
