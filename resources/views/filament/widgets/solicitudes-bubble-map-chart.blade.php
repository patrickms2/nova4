<x-filament-widgets::widget>
    <x-filament::section>
        <div wire:poll.10s="loadBubbleData">
            {{-- Header --}}
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Quickview de Solicitudes — Lanzarote
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Hoteles que están generando solicitudes ahora mismo.
                        <span class="font-medium">{{ $totalBubbles }} hoteles activos</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @if($loading)
                        <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                    @endif
                </div>
            </div>

            {{-- Chart canvas --}}
            <div class="relative overflow-hidden rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <canvas
                    id="lanzarote-bubble-map"
                    style="width: 100%; height: 480px;"
                    wire:ignore
                ></canvas>
            </div>
        </div>
    </x-filament::section>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-geo@4.3.6/build/index.umd.min.js"></script>
    @endassets

    @script
    <script>
        let bubbleChart = null;

        function buildBubbleMap() {
            const canvas = document.getElementById('lanzarote-bubble-map');
            if (!canvas) return;

            const outline = $wire.outline || [];
            const bubbles = $wire.bubbles || [];
            const labels = $wire.labels || [];

            if (bubbleChart) {
                bubbleChart.destroy();
                bubbleChart = null;
            }

            const isDark = document.documentElement.classList.contains('dark');

            bubbleChart = new Chart(canvas.getContext('2d'), {
                type: 'bubbleMap',
                data: {
                    labels: labels,
                    datasets: [{
                        outline: outline,
                        outlineBackgroundColor: isDark
                            ? 'rgba(55, 65, 81, 0.4)'
                            : 'rgba(243, 244, 246, 0.95)',
                        outlineBorderColor: isDark
                            ? 'rgba(156, 163, 175, 0.5)'
                            : 'rgba(148, 163, 184, 0.85)',
                        outlineBorderWidth: 1.5,
                        backgroundColor: 'rgba(239, 68, 68, 0.35)',
                        borderColor: 'rgba(239, 68, 68, 0.9)',
                        borderWidth: 1.5,
                        data: bubbles,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(17,24,39,0.95)' : 'rgba(255,255,255,0.95)',
                            titleColor: isDark ? '#f9fafb' : '#111827',
                            bodyColor: isDark ? '#d1d5db' : '#374151',
                            borderColor: isDark ? 'rgba(75,85,99,0.5)' : 'rgba(209,213,219,0.8)',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(ctx) {
                                    const d = ctx.raw;
                                    const label = ctx.chart.data.labels[ctx.dataIndex] || '';
                                    const pend = d.pendientes || 0;
                                    const lines = [
                                        label,
                                        `📋 ${d.value} solicitudes`,
                                    ];
                                    if (pend > 0) lines.push(`⏳ ${pend} pendientes`);
                                    if (d.municipio) lines.push(`📍 ${d.municipio}`);
                                    return lines;
                                }
                            }
                        },
                        datalabels: false,
                    },
                    scales: {
                        projection: {
                            axis: 'x',
                            projection: 'mercator',
                        },
                        size: {
                            axis: 'x',
                            size: [8, 32],
                            legend: {
                                position: 'bottom-right',
                                align: 'right',
                            },
                        },
                    },
                },
            });
        }

        // Initial render
        $nextTick(() => {
            const waitForChartGeo = setInterval(() => {
                if (typeof Chart !== 'undefined' && Chart.registry?.controllers?.get('bubbleMap')) {
                    clearInterval(waitForChartGeo);
                    buildBubbleMap();
                }
            }, 100);
        });

        // Re-render when Livewire data updates
        $wire.$watch('bubbles', () => {
            buildBubbleMap();
        });
    </script>
    @endscript
</x-filament-widgets::widget>
