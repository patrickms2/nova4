@props([
    'title',
    'description',
])

<div class="col-span-full rounded-2xl border border-dashed border-white/10 px-5 py-10 text-center">
    <p class="text-sm font-medium text-zinc-300">
        {{ $title }}
    </p>

    <p class="mt-1 text-sm text-zinc-600">
        {{ $description }}
    </p>
</div>
