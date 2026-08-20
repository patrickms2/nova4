<div id="portal-load-metric" class="portal-load-metric" data-navigate-once>
    Carga: <span data-role="portal-load-value">--</span> ms
</div>

<script data-navigate-once>
    (() => {
        const updatePortalLoadMetric = () => {
            const metricNode = document.getElementById('portal-load-metric');

            if (! metricNode) {
                return;
            }

            const valueNode = metricNode.querySelector('[data-role="portal-load-value"]');

            if (! valueNode) {
                return;
            }

            const navigationEntry = performance.getEntriesByType('navigation')[0];
            const fallbackDuration = Math.round(performance.now());
            const entryDuration = navigationEntry
                ? Math.round(navigationEntry.loadEventEnd > 0 ? navigationEntry.loadEventEnd : navigationEntry.duration)
                : fallbackDuration;

            valueNode.textContent = String(Math.max(entryDuration, 0));
        };

        if (document.readyState === 'complete') {
            updatePortalLoadMetric();
        } else {
            window.addEventListener('load', updatePortalLoadMetric, { once: true });
        }

        document.addEventListener('livewire:navigated', () => {
            window.requestAnimationFrame(updatePortalLoadMetric);
        });
    })();
</script>
