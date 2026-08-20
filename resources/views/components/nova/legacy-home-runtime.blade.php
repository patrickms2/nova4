@props([
    'enabledSections' => [],
    'runtimeConfigured' => false,
    'portalType' => 'owner',
])

{{-- Fail-open: nunca vaciar la Home cuando Studio aún no tiene configuración válida. --}}
@if($runtimeConfigured && $enabledSections !== [])
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
            'notices' => ['AVISOS', 'Avisos'],
        ];

        $disabledLabels = collect($labels)
            ->reject(fn ($values, $section) => in_array($section, $enabledSections, true))
            ->flatten()
            ->values()
            ->all();
    @endphp

    @if($disabledLabels !== [])
        <script>
            (() => {
                const disabled = @json($disabledLabels);
                const normalize = value => (value || '').replace(/\s+/g, ' ').trim().toUpperCase();

                const findCard = node => {
                    let candidate = node;

                    for (let i = 0; i < 5 && candidate?.parentElement; i++) {
                        const parent = candidate.parentElement;
                        const rect = parent.getBoundingClientRect();
                        const className = String(parent.className || '');

                        const looksLikeCard =
                            rect.width >= 120 &&
                            rect.width <= 650 &&
                            rect.height >= 55 &&
                            rect.height <= 320 &&
                            (
                                parent.tagName === 'ARTICLE' ||
                                parent.tagName === 'A' ||
                                parent.tagName === 'BUTTON' ||
                                className.includes('rounded') ||
                                className.includes('community-glass') ||
                                className.includes('border')
                            );

                        if (!looksLikeCard) {
                            break;
                        }

                        candidate = parent;
                    }

                    return candidate;
                };

                const applyNovaVisibility = () => {
                    document.querySelectorAll('[data-nova-capability-hidden="true"]').forEach(element => {
                        element.style.removeProperty('display');
                        delete element.dataset.novaCapabilityHidden;
                    });

                    const portal = document.querySelector('[data-community-portal]');
                    if (!portal) return;

                    portal.querySelectorAll('main *').forEach(node => {
                        if (node.children.length > 3) return;

                        const text = normalize(node.textContent);
                        if (!disabled.some(label => text === normalize(label))) return;

                        const card = findCard(node);
                        if (!card) return;

                        card.style.setProperty('display', 'none', 'important');
                        card.dataset.novaCapabilityHidden = 'true';
                    });
                };

                const boot = () => {
                    requestAnimationFrame(() => requestAnimationFrame(applyNovaVisibility));

                    if (window.Livewire?.hook) {
                        Livewire.hook('morph.updated', () => {
                            requestAnimationFrame(applyNovaVisibility);
                        });
                    }
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', boot, { once: true });
                } else {
                    boot();
                }

                document.addEventListener('livewire:init', boot, { once: true });
            })();
        </script>
    @endif
@endif
