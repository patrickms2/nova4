@vite(['resources/js/react-flow-panel-builder.jsx'])
<div x-data="reactFlowBuilder()" x-init="init()">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">React Flow Panel Builder</h2>
                <div class="flex items-center space-x-2">
                    <button @click="togglePreview()"
                            class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                        <span x-show="!showPreview">👁️ Preview</span>
                        <span x-show="showPreview">✏️ Edit</span>
                    </button>
                    <button @click="saveFlow()"
                            class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        💾 Save
                    </button>
                    <button @click="generateCode()"
                            class="px-3 py-1 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                        ⚡ Generate Code
                    </button>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <select x-model="currentPanel" @change="loadPanel()"
                        class="text-sm border border-gray-300 rounded-md px-3 py-1">
                    <option value="">Select Panel</option>
                    @foreach($panels as $panel)
                        <option value="{{ $panel->id }}">{{ $panel->name }}</option>
                    @endforeach
                </select>
                <button @click="createNewPanel()"
                        class="px-3 py-1 text-sm bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    ➕ New Panel
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="h-screen pt-16">
        <div id="react-flow-container" class="w-full h-full">
            <!-- React Flow will be mounted here -->
        </div>
    </div>

    <!-- Panel Configuration Modal -->
    <div x-show="showPanelModal"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.away="showPanelModal = false">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Panel Configuration</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Panel Name</label>
                    <input type="text" x-model="panelConfig.name"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model Name</label>
                    <input type="text" x-model="panelConfig.modelName"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea x-model="panelConfig.description" rows="2"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Navigation Group</label>
                    <input type="text" x-model="panelConfig.navigationGroup"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="showPanelModal = false"
                        class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="savePanelConfig()"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Code Preview Modal -->
    <div x-show="showCodeModal"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.away="showCodeModal = false">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 w-full max-w-4xl max-h-[80vh] overflow-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Generated Code</h3>
                <button @click="showCodeModal = false"
                        class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Model</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded text-sm overflow-x-auto"><code x-text="generatedCode.model"></code></pre>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Migration</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded text-sm overflow-x-auto"><code x-text="generatedCode.migration"></code></pre>
                </div>

                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Resource</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded text-sm overflow-x-auto"><code x-text="generatedCode.resource"></code></pre>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="copyCode()"
                        class="px-4 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    📋 Copy All
                </button>
                <button @click="downloadCode()"
                        class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                    📥 Download
                </button>
            </div>
        </div>
    </div>

    <script>
    function reactFlowBuilder() {
        return {
            showPreview: false,
            currentPanel: '',
            showPanelModal: false,
            showCodeModal: false,
            reactFlowInstance: null,
            flowData: {
                nodes: [],
                edges: [],
            },
            panelConfig: {
                name: '',
                modelName: '',
                description: '',
                navigationGroup: '',
            },
            generatedCode: {
                model: '',
                migration: '',
                resource: '',
            },

            init() {
                this.initializeReactFlow();
            },

            initializeReactFlow() {
                const container = document.getElementById('react-flow-container');
                if (container && window.PanelFlowBuilder) {
                    const { createElement } = window.React;
                    const { createRoot } = window.ReactDOM;

                    const App = createElement(window.PanelFlowBuilder, {
                        panelData: this.flowData,
                        onSave: (data) => this.handleSaveFlow(data),
                        onGenerateCode: (data) => this.handleGenerateCode(data),
                    });

                    const root = createRoot(container);
                    root.render(App);
                }
            },

            togglePreview() {
                this.showPreview = !this.showPreview;
            },

            createNewPanel() {
                this.panelConfig = {
                    name: '',
                    modelName: '',
                    description: '',
                    navigationGroup: '',
                };
                this.flowData = {
                    nodes: [],
                    edges: [],
                };
                this.showPanelModal = true;
            },

            async loadPanel() {
                if (!this.currentPanel) return;

                try {
                    const response = await fetch(`/api/panel-builder/panels/${this.currentPanel}`);
                    const data = await response.json();

                    this.panelConfig = {
                        name: data.panel.name,
                        modelName: data.panel.model_schema?.model_name || '',
                        description: data.panel.description,
                        navigationGroup: data.panel.navigation_group,
                    };

                    this.flowData = this.convertPanelToFlowData(data);

                    // Re-initialize React Flow with new data
                    this.initializeReactFlow();
                } catch (error) {
                    console.error('Error loading panel:', error);
                }
            },

            convertPanelToFlowData(data) {
                const nodes = [];
                const edges = [];
                let nodeId = 0;

                // Create model node
                if (data.panel.model_schema?.model_name) {
                    nodes.push({
                        id: `model-${nodeId}`,
                        type: 'model',
                        position: { x: 250, y: 50 },
                        data: {
                            label: data.panel.model_schema.model_name,
                            fields: data.fields || [],
                        },
                    });
                    const modelId = `model-${nodeId}`;
                    nodeId++;
                }

                // Create field nodes
                data.fields?.forEach((field, index) => {
                    const fieldId = `field-${nodeId}`;
                    nodes.push({
                        id: fieldId,
                        type: 'field',
                        position: { x: 50, y: 50 + (index * 80) },
                        data: {
                            label: field.label || field.name,
                            type: field.filament_type,
                            icon: this.getFieldIcon(field.type),
                            required: field.validation_rules?.includes('required') || false,
                        },
                    });

                    // Connect field to model
                    const modelId = nodes.find(n => n.type === 'model')?.id;
                    if (modelId) {
                        edges.push({
                            id: `edge-${fieldId}-${modelId}`,
                            source: fieldId,
                            target: modelId,
                            type: 'smoothstep',
                        });
                    }
                    nodeId++;
                });

                // Create resource node
                if (data.panel.model_schema?.model_name) {
                    nodes.push({
                        id: `resource-${nodeId}`,
                        type: 'resource',
                        position: { x: 500, y: 50 },
                        data: {
                            label: data.panel.model_schema.model_name + 'Resource',
                            resourceType: 'Resource',
                            modelName: data.panel.model_schema.model_name,
                        },
                    });

                    // Connect model to resource
                    const modelId = nodes.find(n => n.type === 'model')?.id;
                    const resourceId = `resource-${nodeId}`;
                    if (modelId) {
                        edges.push({
                            id: `edge-${modelId}-${resourceId}`,
                            source: modelId,
                            target: resourceId,
                            type: 'smoothstep',
                        });
                    }
                    nodeId++;
                }

                // Create table nodes
                data.tables?.forEach((table, index) => {
                    const tableId = `table-${nodeId}`;
                    nodes.push({
                        id: tableId,
                        type: 'table',
                        position: { x: 750, y: 50 + (index * 150) },
                        data: {
                            label: table.title,
                            columns: table.columns || [],
                        },
                    });

                    // Connect resource to table
                    const resourceId = nodes.find(n => n.type === 'resource')?.id;
                    if (resourceId) {
                        edges.push({
                            id: `edge-${resourceId}-${tableId}`,
                            source: resourceId,
                            target: tableId,
                            type: 'smoothstep',
                        });
                    }
                    nodeId++;
                });

                return { nodes, edges };
            },

            getFieldIcon(fieldType) {
                const icons = {
                    string: '📝',
                    text: '📝',
                    textarea: '📄',
                    integer: '🔢',
                    number: '🔢',
                    email: '📧',
                    password: '🔐',
                    select: '📋',
                    checkbox: '☑️',
                    radio: '🔘',
                    toggle: '🔄',
                    date: '📅',
                    datetime: '📆',
                    time: '⏰',
                    file: '📎',
                    image: '🖼️',
                };
                return icons[fieldType] || '📝';
            },

            savePanelConfig() {
                this.showPanelModal = false;
                // Re-initialize React Flow with new panel config
                this.initializeReactFlow();
            },

            async saveFlow() {
                try {
                    const response = await fetch('/api/panel-builder/panels', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        },
                        body: JSON.stringify({
                            name: this.panelConfig.name,
                            description: this.panelConfig.description,
                            navigation_group: this.panelConfig.navigationGroup,
                            model_schema: {
                                model_name: this.panelConfig.modelName,
                                table_name: this.panelConfig.modelName?.toLowerCase() || '',
                                fields: this.extractFieldsFromFlow(),
                                relations: this.extractRelationsFromFlow(),
                            },
                        }),
                    });

                    if (response.ok) {
                        this.showNotification('Panel saved successfully!', 'success');
                    } else {
                        this.showNotification('Error saving panel', 'error');
                    }
                } catch (error) {
                    console.error('Error saving flow:', error);
                    this.showNotification('Error saving panel', 'error');
                }
            },

            async generateCode() {
                try {
                    const response = await fetch('/api/panel-builder/generate-code', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        },
                        body: JSON.stringify({
                            panel_data: {
                                name: this.panelConfig.name,
                                description: this.panelConfig.description,
                                model_schema: {
                                    model_name: this.panelConfig.modelName,
                                    table_name: this.panelConfig.modelName?.toLowerCase() || '',
                                    fields: this.extractFieldsFromFlow(),
                                    relations: this.extractRelationsFromFlow(),
                                },
                            },
                            fields_data: this.extractFieldsFromFlow(),
                            tables_data: this.extractTablesFromFlow(),
                        }),
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.generatedCode = data.code;
                        this.showCodeModal = true;
                        this.showNotification('Code generated successfully!', 'success');
                    } else {
                        this.showNotification('Error generating code', 'error');
                    }
                } catch (error) {
                    console.error('Error generating code:', error);
                    this.showNotification('Error generating code', 'error');
                }
            },

            extractFieldsFromFlow() {
                const fieldNodes = this.flowData.nodes.filter(n => n.type === 'field');
                return fieldNodes.map(node => ({
                    name: node.data.label?.toLowerCase().replace(/\s+/g, '_') || 'field',
                    label: node.data.label || 'Field',
                    type: this.getMigrationType(node.data.type),
                    filament_type: node.data.type,
                    column_type: this.getColumnType(node.data.type),
                    nullable: !node.data.required,
                    validation_rules: node.data.required ? ['required'] : [],
                    field_config: {
                        placeholder: '',
                    },
                }));
            },

            extractRelationsFromFlow() {
                // Extract relations from edges between model and other nodes
                return [];
            },

            extractTablesFromFlow() {
                const tableNodes = this.flowData.nodes.filter(n => n.type === 'table');
                return tableNodes.map(node => ({
                    name: node.data.label?.toLowerCase().replace(/\s+/g, '_') || 'table',
                    title: node.data.label || 'Table',
                    description: '',
                    columns: node.data.columns || [],
                    filters: [],
                    actions: ['edit', 'delete'],
                    bulk_actions: ['delete'],
                    is_default: true,
                }));
            },

            getMigrationType(fieldType) {
                const types = {
                    TextInput: 'string',
                    Textarea: 'text',
                    NumberInput: 'integer',
                    EmailInput: 'string',
                    PasswordInput: 'string',
                    Select: 'string',
                    Checkbox: 'boolean',
                    Toggle: 'boolean',
                    DatePicker: 'date',
                    DateTimePicker: 'datetime',
                    TimePicker: 'time',
                    FileUpload: 'string',
                    RichEditor: 'text',
                };
                return types[fieldType] || 'string';
            },

            getColumnType(fieldType) {
                const types = {
                    TextInput: 'TextColumn',
                    Textarea: 'TextColumn',
                    NumberInput: 'TextColumn',
                    EmailInput: 'TextColumn',
                    PasswordInput: 'TextColumn',
                    Select: 'TextColumn',
                    Checkbox: 'IconColumn',
                    Toggle: 'IconColumn',
                    DatePicker: 'DateColumn',
                    DateTimePicker: 'DateTimeColumn',
                    TimePicker: 'TimeColumn',
                    FileUpload: 'TextColumn',
                    RichEditor: 'TextColumn',
                };
                return types[fieldType] || 'TextColumn';
            },

            handleSaveFlow(data) {
                this.flowData = data;
            },

            handleGenerateCode(data) {
                this.flowData = data;
                this.generateCode();
            },

            copyCode() {
                const allCode = Object.values(this.generatedCode).join('\n\n');
                navigator.clipboard.writeText(allCode);
                this.showNotification('Code copied to clipboard!', 'success');
            },

            downloadCode() {
                const allCode = Object.entries(this.generatedCode)
                    .map(([type, code]) => `// ${type.toUpperCase()}\n${code}`)
                    .join('\n\n');

                const blob = new Blob([allCode], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${this.panelConfig.modelName || 'panel'}_code.txt`;
                a.click();
                URL.revokeObjectURL(url);
            },

            showNotification(message, type = 'info') {
                // Simple notification implementation
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 24px;
                    border-radius: 6px;
                    color: white;
                    font-weight: 500;
                    z-index: 9999;
                    background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            },
        };
    }
    </script>
</div>

<style>
/* React Flow Builder Styles */
.panel-flow-builder {
    width: 100%;
    height: 100%;
}

.flow-toolbar {
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.flow-toolbar h3 {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.toolbar-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.toolbar-buttons button {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: white;
    color: #374151;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.toolbar-buttons button:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.flow-actions {
    display: flex;
    gap: 8px;
}

.save-btn, .generate-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.save-btn {
    background: #3b82f6;
}

.save-btn:hover {
    background: #2563eb;
}

.generate-btn {
    background: #10b981;
}

.generate-btn:hover {
    background: #059669;
}

/* Custom Node Styles */
.model-node, .resource-node, .table-node, .field-node {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    min-width: 180px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.model-node.selected, .resource-node.selected, .table-node.selected, .field-node.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.node-header {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 6px 6px 0 0;
}

.node-icon {
    font-size: 16px;
    margin-right: 8px;
}

.node-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
}

.node-content {
    padding: 12px;
}

.model-fields, .table-columns {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.field-item, .column-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2px 4px;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 12px;
}

.field-type, .column-type {
    color: #6b7280;
    font-size: 10px;
}

.field-name, .column-name {
    color: #1f2937;
    font-weight: 500;
}

.resource-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.resource-type, .resource-model {
    font-size: 12px;
    color: #6b7280;
}

.field-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.field-type {
    color: #6b7280;
    font-size: 12px;
}

.field-validation {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 12px;
    background: #ef4444;
    color: white;
}

/* Edit Modal Styles */
.edit-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    padding: 24px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-content h3 {
    margin: 0 0 16px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
}

.modal-actions button {
    padding: 8px 16px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.modal-actions button:first-child {
    background: white;
    color: #374151;
}

.modal-actions button:first-child:hover {
    background: #f3f4f6;
}

.modal-actions button:last-child {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.modal-actions button:last-child:hover {
    background: #2563eb;
}

/* Node action bar */
.node-action-btn {
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: white;
    font-size: 11px;
    cursor: pointer;
    margin-right: 4px;
    transition: all 0.2s;
}

.node-action-btn:hover {
    background: #f3f4f6;
}

.node-action-btn.info {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1e40af;
}

.node-action-btn.danger {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #991b1b;
}

/* Add node button in toolbar */
.add-node-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    background: #8b5cf6;
}

.add-node-btn:hover {
    background: #7c3aed;
}

/* NOVA semantic nodes */
.nova-node {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    min-width: 180px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.nova-node.selected {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
}

.nova-type {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #8b5cf6;
    display: block;
    margin-bottom: 4px;
}

.nova-description {
    font-size: 12px;
    color: #6b7280;
    margin: 4px 0;
    line-height: 1.4;
}

/* Presentation toggles & badges */
.presentation-toggles {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.presentation-toggle {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
}

.presentation-toggle input {
    width: auto;
    margin: 0;
}

.presentation-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}

.presentation-badge {
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    padding: 2px 6px;
    border-radius: 12px;
    background: #e0e7ff;
    color: #3730a3;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .model-node, .resource-node, .table-node, .field-node {
        background: #1f2937;
        border-color: #374151;
    }

    .node-header {
        background: #374151;
        border-bottom-color: #4b5563;
    }

    .node-title {
        color: #f9fafb;
    }

    .field-item, .column-item {
        background: #374151;
    }

    .field-name, .column-name {
        color: #f9fafb;
    }

    .modal-content {
        background: #1f2937;
    }

    .modal-content h3 {
        color: #f9fafb;
    }

    .form-group label {
        color: #d1d5db;
    }

    .form-group input, .form-group select, .form-group textarea {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
}
</style>
