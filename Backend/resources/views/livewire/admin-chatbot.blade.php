<div
    x-data="{ open: @entangle('isOpen') }"
    style="position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 50;"
>
    {{-- Chat Window --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        x-cloak
        class="mb-4 w-96 max-w-[calc(100vw-3rem)] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col"
        style="height: 500px;"
    >
        {{-- Header --}}
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-sm">Setup Assistant</h3>
                    <p class="text-white/70 text-xs">Ask me about program setup</p>
                </div>
            </div>
            <button
                wire:click="toggle"
                class="text-white/80 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/10"
            >
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div
            class="flex-1 overflow-y-auto p-4 space-y-4"
            x-ref="chatMessages"
            x-effect="$nextTick(() => { if ($refs.chatMessages) $refs.chatMessages.scrollTop = $refs.chatMessages.scrollHeight })"
        >
            @foreach ($messages as $message)
                <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] {{ $message['role'] === 'user'
                        ? 'bg-primary-500 text-white rounded-2xl rounded-br-md'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-2xl rounded-bl-md' }} px-4 py-2.5 text-sm">
                        {!! nl2br(e($message['content'])) !!}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Quick Actions --}}
        @if (count($messages) <= 1)
            <div class="px-4 pb-2">
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['How do I set up a program?', 'What are stages?', 'Help with AI scoring'] as $suggestion)
                        <button
                            wire:click="$set('userMessage', '{{ $suggestion }}')"
                            x-on:click="$nextTick(() => $wire.sendMessage())"
                            class="text-xs px-2.5 py-1.5 rounded-full bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/40 transition-colors border border-primary-200 dark:border-primary-800"
                        >
                            {{ $suggestion }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Input --}}
        <div class="border-t border-gray-200 dark:border-gray-700 p-3">
            <form wire:submit="sendMessage" class="flex gap-2">
                <input
                    type="text"
                    wire:model="userMessage"
                    placeholder="Ask me anything..."
                    class="flex-1 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    autocomplete="off"
                />
                <button
                    type="submit"
                    class="bg-primary-500 hover:bg-primary-600 text-white rounded-xl px-3 py-2 transition-colors flex items-center justify-center"
                >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Floating Button --}}
    <button
        x-show="!open"
        wire:click="toggle"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        style="height:3.5rem;width:3.5rem;border-radius:9999px;background:linear-gradient(135deg,#25935F,#1B8354);color:white;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);transition:all 0.2s;display:flex;align-items:center;justify-content:center;cursor:pointer;border:none;padding:0;line-height:1;"
    >
        <svg style="height:1.5rem;width:1.5rem;display:block;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
        </svg>
    </button>
</div>
