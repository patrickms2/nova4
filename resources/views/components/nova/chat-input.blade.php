<form wire:submit="submitPrompt" class="sticky bottom-0 bg-black py-4">
    <div class="flex items-center gap-4 rounded-2xl border border-neutral-800 bg-neutral-900 p-2 shadow-sm transition-colors focus-within:border-neutral-700">
        <label for="nova-prompt" class="sr-only">Describe what you would like Nova to evolve</label>
        <input
            id="nova-prompt"
            wire:model="prompt"
            type="text"
            placeholder="Connect WhatsApp to my reservations…"
            autocomplete="off"
            class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm text-white placeholder:text-neutral-400 focus:ring-0"
        >
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="submitPrompt"
            class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-semibold text-black shadow-sm transition-colors hover:bg-orange-400 disabled:cursor-wait disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="submitPrompt">Send</span>
            <span wire:loading wire:target="submitPrompt">Planning…</span>
        </button>
    </div>
</form>
