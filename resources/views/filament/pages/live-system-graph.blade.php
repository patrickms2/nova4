<x-filament-panels::page>

    @php
        $nodesJson = json_encode($nodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $edgesJson = json_encode($edges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $viewportJson = json_encode($viewport, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    @endphp

    @if (empty($nodes))
        <div class="fi-section-content-ctn rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No hay Workspace activo. Crea uno desde Studio para ver el Live System Graph.
            </p>
        </div>
    @else
        <div class="fi-section-content-ctn rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            x-data x-init="
                 $nextTick(() => {
                     if (window.mountSignalFlowEditor) {
                         window.mountSignalFlowEditor(document.getElementById('nova-live-system-graph'));
                     } else {
                         setTimeout(() => {
                             if (window.mountSignalFlowEditor) {
                                  window.mountSignalFlowEditor(document.getElementById('nova-live-system-graph'));
                             }
                         }, 500);
                     }
                 })
             ">
            <div id="nova-live-system-graph" style="width: 100%; height: calc(100vh - 300px); min-height: 600px;"
                data-nodes='{!! $nodesJson !!}'
                data-edges='{!! $edgesJson !!}'
                data-viewport='{!! $viewportJson !!}'
                data-event-options='{}'
                data-filter-fields-map='{}'
                data-available-nodes='[]'
                data-credentials='[]'
                data-workflow-name='@json(__("NOVA — Live System Graph"))'
                data-workflow-description='@json(__("Read-only view. Reconstructed from canonical NOVA Definition."))'
                data-app-name='@json(config("app.name"))'
                data-flow-read-only="1"
                data-flow-i18n='{}'
                wire:ignore>
            </div>
        </div>
    @endif

</x-filament-panels::page>
