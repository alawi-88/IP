@php
    /** @var array<int, array{index:int,status:string,decision_maker:?string,comment:?string,created_at:mixed}> $items */
    $items = $getState() ?? [];

    $color = fn (string $status) => match ($status) {
        'approved' => 'bg-success-600',
        'rejected' => 'bg-danger-600',
        default => 'bg-gray-400',
    };
@endphp

<div class="mt-2">
    <ol class="relative border-s border-gray-200 dark:border-gray-700 ms-3">
        @foreach ($items as $i => $item)
            @php
                $isPending = ($item['status'] ?? 'pending') === 'pending';
                $title = 'Decision ' . ($item['index'] ?? ($i + 1));
                $decisionMaker = $item['decision_maker'] ?? null;
                $comment = $item['comment'] ?? null;
                $createdAt = $item['created_at'] ?? null;
            @endphp

            <li class="mb-5 ms-6">
              

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</span>

                    @if (($item['status'] ?? 'pending') === 'approved')
                        <span class="text-xs font-medium text-success-700 dark:text-success-300">Approved / موافق</span>
                    @elseif (($item['status'] ?? 'pending') === 'rejected')
                        <span class="text-xs font-medium text-danger-700 dark:text-danger-300">Rejected / مرفوض</span>
                    @else
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Pending / قيد الانتظار</span>
                    @endif

                    @if ($createdAt)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ \Illuminate\Support\Carbon::parse($createdAt)->format('M d, Y H:i') }}
                        </span>
                    @endif
                </div>

                @if (! $isPending)
                    <div class="mt-1 ms-2 text-sm text-gray-700 dark:text-gray-200">
                        <div>
                            <span class="font-medium">Decision Maker / صاحب القرار : </span>
                            @if ($decisionMaker)
                                <span class="ms-2">{{ $decisionMaker }}</span>
                            @else
                                <span class="ms-2 text-gray-500 dark:text-gray-400">—</span>
                            @endif
                        </div>

                        <div class="mt-1">
                            <span class="font-medium">Comment / تعليق : </span>
                            @if (!empty($comment))
                                <span class="ms-2">{{ $comment }}</span>
                            @else
                                <span class="ms-2 text-gray-500 dark:text-gray-400">—</span>
                            @endif
                        </div>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</div>


