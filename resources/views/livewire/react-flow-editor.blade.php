<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <svg class="fi-icon fi-size-lg text-purple-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">React Flow Editor</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button wire:click="saveFlowDiagram" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <svg class="fi-icon fi-size-sm mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Guardar Diagrama
                    </button>
                    <button wire:click="backToPanelManager" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        <svg class="fi-icon fi-size-sm mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-800 dark:to-blue-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Editor Visual de Panel</h2>
                        <p class="text-purple-100">Diseña tu panel arrastrando y conectando nodos</p>
                    </div>
                    <div class="flex space-x-2">
                        <button wire:click="exportFlow" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <svg class="fi-icon fi-size-sm inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Exportar
                        </button>
                        <button wire:click="importFlow" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <svg class="fi-icon fi-size-sm inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Importar
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex h-screen">
                <!-- Sidebar with Node Palette -->
                <div class="w-64 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-medium text-gray-900 dark:text-white mb-4">Paleta de Nodos</h3>

                    <div class="space-y-4">
                        <!-- Field Nodes -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Campos</h4>
                            <div class="space-y-2">
                                <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors" draggable="true" data-node-type="text-input">
                                    📝 TextInput
                                </div>
                                <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors" draggable="true" data-node-type="textarea">
                                    📄 Textarea
                                </div>
                                <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors" draggable="true" data-node-type="select">
                                    📋 Select
                                </div>
                                <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors" draggable="true" data-node-type="checkbox">
                                    ☑️ Checkbox
                                </div>
                                <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors" draggable="true" data-node-type="date-picker">
                                    📅 DatePicker
                                </div>
                            </div>
                        </div>

                        <!-- Relation Nodes -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Relaciones</h4>
                            <div class="space-y-2">
                                <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-green-200 dark:hover:bg-green-800 transition-colors" draggable="true" data-node-type="belongs-to">
                                    🔗 BelongsTo
                                </div>
                                <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-green-200 dark:hover:bg-green-800 transition-colors" draggable="true" data-node-type="has-many">
                                    🌐 HasMany
                                </div>
                                <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-green-200 dark:hover:bg-green-800 transition-colors" draggable="true" data-node-type="belongs-to-many">
                                    🔄 BelongsToMany
                                </div>
                            </div>
                        </div>

                        <!-- Table Nodes -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tablas</h4>
                            <div class="space-y-2">
                                <div class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-purple-200 dark:hover:bg-purple-800 transition-colors" draggable="true" data-node-type="table">
                                    📊 Table
                                </div>
                                <div class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-3 py-2 rounded-lg text-sm font-medium cursor-move hover:bg-purple-200 dark:hover:bg-purple-800 transition-colors" draggable="true" data-node-type="grid">
                                    📋 Grid
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- React Flow Canvas -->
                <div class="flex-1 relative">
                    <div id="react-flow-container" class="w-full h-full bg-gray-100 dark:bg-gray-800">
                        <!-- React Flow will be mounted here -->
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="fi-icon fi-size-4xl text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Canvas React Flow</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Arrastra nodos desde la paleta para comenzar</p>

                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 max-w-md mx-auto">
                                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Cómo usar:</h4>
                                    <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 text-left">
                                        <li>• Arrastra nodos desde la paleta izquierda</li>
                                        <li>• Conecta nodos para definir relaciones</li>
                                        <li>• Haz doble clic para editar propiedades</li>
                                        <li>• Usa el panel derecho para configurar</li>
                                        <li>• Guarda el diagrama cuando termines</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Properties Panel -->
                    <div class="absolute right-0 top-0 w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 p-4 h-full overflow-y-auto">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-4">Propiedades</h3>

                        <div class="space-y-4">
                            <!-- Selected Node Info -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nodo Seleccionado</h4>
                                <div id="selected-node-info" class="text-sm text-gray-600 dark:text-gray-400">
                                    Ningún nodo seleccionado
                                </div>
                            </div>

                            <!-- Node Properties -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Configuración</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label>
                                        <input type="text" id="node-name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm" placeholder="nombre_campo">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Etiqueta</label>
                                        <input type="text" id="node-label" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm" placeholder="Nombre del Campo">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                                        <select id="node-type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm">
                                            <option>string</option>
                                            <option>text</option>
                                            <option>integer</option>
                                            <option>boolean</option>
                                            <option>date</option>
                                            <option>datetime</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" id="node-required" class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-500 focus:ring-purple-500 mr-2">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Requerido</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Flow Actions -->
                            <div class="space-y-2">
                                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Aplicar Cambios
                                </button>
                                <button class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Eliminar Nodo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- React Flow Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize drag and drop for node palette
    const draggableNodes = document.querySelectorAll('[draggable="true"]');
    const flowContainer = document.getElementById('react-flow-container');

    draggableNodes.forEach(node => {
        node.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('nodeType', node.dataset.nodeType);
            node.style.opacity = '0.5';
        });

        node.addEventListener('dragend', (e) => {
            node.style.opacity = '1';
        });
    });

    flowContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        flowContainer.classList.add('bg-purple-50', 'dark:bg-purple-900/20');
    });

    flowContainer.addEventListener('dragleave', (e) => {
        flowContainer.classList.remove('bg-purple-50', 'dark:bg-purple-900/20');
    });

    flowContainer.addEventListener('drop', (e) => {
        e.preventDefault();
        flowContainer.classList.remove('bg-purple-50', 'dark:bg-purple-900/20');

        const nodeType = e.dataTransfer.getData('nodeType');
        const rect = flowContainer.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Create visual node
        createVisualNode(nodeType, x, y);
    });

    function createVisualNode(type, x, y) {
        const node = document.createElement('div');
        node.className = 'absolute bg-white dark:bg-gray-800 border-2 border-purple-500 rounded-lg p-3 shadow-lg cursor-move';
        node.style.left = x + 'px';
        node.style.top = y + 'px';
        node.style.minWidth = '150px';

        const nodeIcons = {
            'text-input': '📝',
            'textarea': '📄',
            'select': '📋',
            'checkbox': '☑️',
            'date-picker': '📅',
            'belongs-to': '🔗',
            'has-many': '🌐',
            'belongs-to-many': '🔄',
            'table': '📊',
            'grid': '📋'
        };

        const nodeNames = {
            'text-input': 'TextInput',
            'textarea': 'Textarea',
            'select': 'Select',
            'checkbox': 'Checkbox',
            'date-picker': 'DatePicker',
            'belongs-to': 'BelongsTo',
            'has-many': 'HasMany',
            'belongs-to-many': 'BelongsToMany',
            'table': 'Table',
            'grid': 'Grid'
        };

        node.innerHTML = `
            <div class="flex items-center space-x-2">
                <span class="text-lg">${nodeIcons[type] || '📦'}</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">${nodeNames[type] || 'Node'}</span>
            </div>
            <div class="mt-2">
                <input type="text" class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-xs" placeholder="nombre" value="${type.replace('-', '_')}">
            </div>
        `;

        flowContainer.appendChild(node);

        // Make node draggable within the container
        makeNodeDraggable(node);

        // Select node when clicked
        node.addEventListener('click', () => selectNode(node, type));
    }

    function makeNodeDraggable(node) {
        let isDragging = false;
        let startX, startY, initialX, initialY;

        node.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialX = node.offsetLeft;
            initialY = node.offsetTop;
            node.style.zIndex = '1000';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            node.style.left = (initialX + dx) + 'px';
            node.style.top = (initialY + dy) + 'px';
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            node.style.zIndex = '';
        });
    }

    function selectNode(node, type) {
        // Remove previous selection
        document.querySelectorAll('.border-purple-500').forEach(n => {
            if (n !== node) {
                n.classList.remove('border-purple-500');
                n.classList.add('border-gray-300');
            }
        });

        // Highlight selected node
        node.classList.remove('border-gray-300');
        node.classList.add('border-purple-500');

        // Update properties panel
        document.getElementById('selected-node-info').innerHTML = `
            <div class="space-y-2">
                <div><strong>Tipo:</strong> ${type}</div>
                <div><strong>Posición:</strong> (${node.offsetLeft}, ${node.offsetTop})</div>
            </div>
        `;
    }
});
</script>
