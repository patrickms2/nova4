<div
    x-data="{ expanded: false }"
    class="fixed bottom-8 right-8 z-40"
>

    <div
        x-on:mouseenter="expanded = true"
        x-on:mouseleave="expanded = false"
        class="transition-all duration-300"
    >

        <div
            :class="expanded ? 'px-6 py-4 rounded-2xl' : 'w-14 h-14 rounded-full'"
            class="bg-gradient-to-br from-red-500 to-orange-500 text-white shadow-xl flex items-center justify-center cursor-pointer transition-all duration-300"
        >

            <span x-show="expanded" class="font-semibold">
                Nueva Acción
            </span>

            <svg x-show="!expanded" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>

        </div>

    </div>

</div>