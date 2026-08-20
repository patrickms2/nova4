import './bootstrap.js';
import { registerBlatUI } from './blatui-core.js'
import collapse from '@alpinejs/collapse'

Alpine.plugin(collapse)
document.addEventListener('alpine:init', () => {
    registerBlatUI(window.Alpine)
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
