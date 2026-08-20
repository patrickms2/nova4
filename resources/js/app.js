import './bootstrap.js';

import { registerBlatUI } from './blatui-core.js'
import { node as alpineFlowNode, flowEditor } from './vendor/alpine-flow/index.js'
import './vendor/alpine-flow/flow.css'
import './nova-graph.css'

document.addEventListener('alpine:init', () => {
    registerBlatUI(window.Alpine)
    window.Alpine.plugin(alpineFlowNode)
    window.Alpine.data('flowEditor', flowEditor)

    window.Alpine.data('novaGraphPalette', () => ({
        nodeMenu: {
            open: false,
            x: 0,
            y: 0,
            nodeId: null,
            nodeType: null,
            canDelete: false,
        },
        init() {
            window.novaFinishConnection = (targetId) => {
                const sourceId = window.__novaConnecting;
                window.__novaConnecting = null;

                if (!sourceId || sourceId === targetId) {
                    return;
                }

                const type = prompt('Tipo de relación (ej: pertenece a, requiere, envía)', 'relatesTo');

                if (type) {
                    this.$wire.addRelation(sourceId, targetId, type);
                }
            };
        },
        handleDrop(e) {
            const type = e.dataTransfer.getData('type');

            if (type === 'capability') {
                const id = e.dataTransfer.getData('id');
                this.$wire.addCapability(id);
                return;
            }

            if (type === 'action') {
                const target = document.elementFromPoint(e.clientX, e.clientY)?.closest('[data-capability]');

                if (!target) {
                    alert('Suelta la acción sobre una capacidad para asociarla.');
                    return;
                }

                const label = prompt('Nombre de la acción');

                if (label) {
                    this.$wire.addCustomAction(label, target.dataset.capability);
                }
            }
        },
        openNodeMenu(event, nodeId, nodeType, canDelete) {
            this.nodeMenu = {
                open: true,
                x: event.clientX,
                y: event.clientY,
                nodeId,
                nodeType,
                canDelete: !!canDelete,
            };
        },
        closeNodeMenu() {
            this.nodeMenu.open = false;
        },
        confirmDeleteNode() {
            const { nodeId, nodeType } = this.nodeMenu;
            this.closeNodeMenu();

            const message = nodeType === 'resource'
                ? '¿Eliminar este recurso? Esto también elimina la(s) capacidad(es) que lo producen.'
                : '¿Eliminar este nodo? Esta acción no se puede deshacer.';

            if (confirm(message)) {
                this.$wire.deleteNode(nodeId);
            }
        },
    }))
})

// Load React Flow dependencies dynamically when needed
window.loadReactFlow = async function() {
    try {
        // Load React and React DOM
        await loadScript('/node_modules/react/umd/react.production.min.js');
        await loadScript('/node_modules/react-dom/umd/react-dom.production.min.js');

        // Load React Flow
        await loadScript('/node_modules/reactflow/dist/umd/index.js');
        await loadStyle('/node_modules/reactflow/dist/style.css');

        // Load custom React Flow components
        await loadScript('/js/react-flow-panel-builder.js');

        return true;
    } catch (error) {
        console.error('Error loading React Flow:', error);
        return false;
    }
};

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function loadStyle(href) {
    return new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.onload = resolve;
        link.onerror = reject;
        document.head.appendChild(link);
    });
}
