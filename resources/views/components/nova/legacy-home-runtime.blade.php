@props([
    'enabledSections' => [],
    'portalType' => 'owner',
])

@php
$labels = [
    'properties' => ['PROPIEDADES', 'Propiedades'],
    'documents' => ['DOCUMENTOS', 'Documentos'],
    'fees' => ['CUOTAS', 'Cuotas'],
    'appointments' => ['CITAS', 'Citas'],
    'tickets' => ['TICKETS', 'Tickets'],
    'incidents' => ['INCIDENCIAS', 'Incidencias'],
    'plans' => ['PLANES', 'Planes'],
    'work' => ['ÓRDENES', 'Ordenes', 'Órdenes'],
    'shifts' => ['TURNOS', 'Turnos'],
    'attendance' => ['ASISTENCIA', 'Asistencia'],
    'expenses' => ['GASTOS', 'Gastos'],
    'communities' => ['COMUNIDADES', 'Comunidades'],
];
$disabledLabels = collect($labels)
    ->reject(fn ($values, $section) => in_array($section, $enabledSections, true))
    ->flatten()
    ->values()
    ->all();
@endphp

@if($disabledLabels !== [])
<script>
document.addEventListener('DOMContentLoaded', () => {
    const disabled = @json($disabledLabels);
    const normalize = value => (value || '').replace(/\s+/g, ' ').trim().toUpperCase();

    const hideLegacyTiles = () => {
        for (const node of document.querySelectorAll('body *')) {
            if (node.children.length > 4) continue;

            const ownText = normalize(node.textContent);
            const matched = disabled.some(label => ownText === normalize(label));

            if (!matched) continue;

            let candidate = node;
            for (let i = 0; i < 4 && candidate.parentElement; i++) {
                const parent = candidate.parentElement;
                const rect = parent.getBoundingClientRect();

                if (rect.width >= 140 && rect.width <= 520 && rect.height >= 70 && rect.height <= 260) {
                    candidate = parent;
                } else {
                    break;
                }
            }

            candidate.style.display = 'none';
            candidate.dataset.novaCapabilityHidden = 'true';
        }
    };

    hideLegacyTiles();

    new MutationObserver(hideLegacyTiles).observe(document.body, {
        childList: true,
        subtree: true,
    });
});
</script>
@endif
