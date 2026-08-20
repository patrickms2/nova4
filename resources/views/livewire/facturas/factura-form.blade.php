<div class="space-y-6">
    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">
                Nueva factura
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Editor dinámico con IGIC 7% y retención 15% (modo C 🔥)
            </p>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <button
                type="button"
                wire:click="save"
                class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-4 py-1.5 text-white shadow-sm hover:bg-emerald-700 text-xs"
            >
                💾 Guardar factura
            </button>
        </div>
    </div>

    {{-- Layout principal --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Columna izquierda: datos factura + cliente --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-slate-100 bg-white/80 backdrop-blur px-4 py-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600">
                            Serie
                        </label>
                        <input
                            type="text"
                            wire:model="serie"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:border-emerald-400 focus:ring-emerald-400"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">
                            Número
                        </label>
                        <input
                            type="text"
                            wire:model="numero"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:border-emerald-400 focus:ring-emerald-400"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">
                            Fecha
                        </label>
                        <input
                            type="date"
                            wire:model="fecha"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:border-emerald-400 focus:ring-emerald-400"
                        >
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white/80 backdrop-blur px-4 py-4 shadow-sm">
                <livewire:facturas.cliente-selector :cliente-id="$cliente_id" />
            </div>

            {{-- Líneas de factura --}}
            <div class="rounded-2xl border border-slate-100 bg-white/80 backdrop-blur px-4 py-4 shadow-sm space-y-3">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-semibold text-slate-800">
                        Líneas de factura
                    </h2>
                    <button
                        type="button"
                        wire:click="addLinea"
                        class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] text-emerald-700 hover:bg-emerald-100"
                    >
                        ➕ Añadir línea
                    </button>
                </div>

                <div class="space-y-2">
                    @foreach($lineas as $index => $linea)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-3 py-3 flex flex-col gap-2">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <livewire:facturas.concepto-selector
                                        :line-index="$index"
                                        :concepto-id="$linea['concepto_id'] ?? null"
                                        :wire:key="'concepto-'.$index"
                                    />
                                </div>

                                <div class="w-20">
                                    <label class="block text-[10px] text-slate-500">
                                        Cant.
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.lazy="lineas.{{ $index }}.cantidad"
                                        class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:border-emerald-400 focus:ring-emerald-400"
                                    >
                                </div>

                                <div class="w-24">
                                    <label class="block text-[10px] text-slate-500">
                                        Precio
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.lazy="lineas.{{ $index }}.precio"
                                        class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:border-emerald-400 focus:ring-emerald-400"
                                    >
                                </div>

                                <div class="w-20">
                                    <label class="block text-[10px] text-slate-500">
                                        Desc. %
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.lazy="lineas.{{ $index }}.descuento"
                                        class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:border-emerald-400 focus:ring-emerald-400"
                                    >
                                </div>

                                <div class="w-24">
                                    <label class="block text-[10px] text-slate-500">
                                        IGIC %
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.lazy="lineas.{{ $index }}.igic"
                                        class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:border-emerald-400 focus:ring-emerald-400"
                                    >
                                </div>

                                <div class="w-24">
                                    <label class="block text-[10px] text-slate-500">
                                        Ret. %
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.lazy="lineas.{{ $index }}.retencion"
                                        class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:border-emerald-400 focus:ring-emerald-400"
                                    >
                                </div>

                                <div class="w-28 text-right">
                                    <label class="block text-[10px] text-slate-500">
                                        Total línea
                                    </label>
                                    <div class="mt-2 text-xs font-semibold text-slate-900">
                                        {{ number_format($linea['total'] ?? 0, 2) }} €
                                    </div>
                                </div>

                                <div class="pt-5">
                                    <button
                                        type="button"
                                        wire:click="removeLinea({{ $index }})"
                                        class="text-[11px] text-rose-500 hover:text-rose-700"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] text-slate-500">
                                    Descripción
                                </label>
                                <input
                                    type="text"
                                    wire:model.lazy="lineas.{{ $index }}.descripcion"
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:border-emerald-400 focus:ring-emerald-400"
                                    placeholder="Detalle adicional (opcional)…"
                                >
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Columna derecha: totales --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-4 text-slate-50 shadow-lg">
                <h2 class="text-sm font-semibold mb-2">
                    Resumen económico
                </h2>

                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-slate-300">Base imponible</dt>
                        <dd class="font-semibold">{{ number_format($subtotal, 2) }} €</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-300">IGIC (7%)</dt>
                        <dd class="font-semibold">{{ number_format($total_igic, 2) }} €</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-300">Retención (15%)</dt>
                        <dd class="font-semibold">-{{ number_format($total_retencion, 2) }} €</dd>
                    </div>
                </dl>

                <div class="border-t border-slate-700 mt-3 pt-3 flex items-center justify-between">
                    <span class="text-xs text-slate-300">Total factura</span>
                    <span class="text-lg font-semibold">
                        {{ number_format($total_factura, 2) }} €
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white/80 px-4 py-4 shadow-sm">
                <label class="block text-xs font-medium text-slate-600">
                    Notas internas / observaciones
                </label>
                <textarea
                    wire:model="notas"
                    rows="4"
                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:border-emerald-400 focus:ring-emerald-400"
                    placeholder="Notas internas para esta factura…"
                ></textarea>
            </div>
        </div>
    </div>
</div>
