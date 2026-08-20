@props([
    'role',
    'content',
])

<article
    @class([
        'grid grid-cols-[32px_minmax(0,1fr)] gap-4 py-6',
        'border-t border-neutral-800' => $role !== 'user',
    ])
>
    <div
        @class([
            'flex size-8 items-center justify-center rounded-2xl text-[10px] font-semibold uppercase tracking-widest',
            'bg-orange-500 text-black' => $role === 'user',
            'bg-neutral-800 text-white' => $role !== 'user',
        ])
    >
        {{ $role === 'user' ? 'You' : 'N' }}
    </div>
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">
            {{ $role === 'user' ? 'You' : 'Nova' }}
        </p>
        <p class="mt-3 max-w-3xl text-[15px] leading-7 text-white">
            {{ $content }}
        </p>
    </div>
</article>
