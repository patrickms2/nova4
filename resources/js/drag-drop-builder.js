// Drag and Drop Panel Builder JavaScript
class PanelBuilder {
    constructor() {
        this.initializeDragDrop();
        this.initializeFieldBuilder();
        this.initializeRelationBuilder();
        this.initializeTableBuilder();
    }

    initializeDragDrop() {
        // Make panels draggable
        this.makeDraggable('.panel-card', '.panel-grid');

        // Make fields sortable within panels
        this.makeSortable('.field-list', '.field-item');

        // Initialize drop zones for different field types
        this.initializeDropZones();
    }

    makeDraggable(selector, container) {
        const items = document.querySelectorAll(selector);
        items.forEach(item => {
            item.draggable = true;

            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', e.target.innerHTML);
                e.target.classList.add('dragging');
            });

            item.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
            });
        });

        const containerEl = document.querySelector(container);
        if (containerEl) {
            containerEl.addEventListener('dragover', (e) => {
                e.preventDefault();
                const dragging = document.querySelector('.dragging');
                const afterElement = this.getDragAfterElement(containerEl, e.clientY);

                if (afterElement == null) {
                    containerEl.appendChild(dragging);
                } else {
                    containerEl.insertBefore(dragging, afterElement);
                }
            });
        }
    }

    makeSortable(selector, itemSelector) {
        const lists = document.querySelectorAll(selector);
        lists.forEach(list => {
            new Sortable(list, {
                group: 'fields',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: (evt) => {
                    this.updateFieldOrder(evt);
                }
            });
        });
    }

    initializeDropZones() {
        const dropZones = document.querySelectorAll('.drop-zone');
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('drop-zone-active');
            });

            zone.addEventListener('dragleave', (e) => {
                zone.classList.remove('drop-zone-active');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('drop-zone-active');
                this.handleDrop(e, zone);
            });
        });
    }

    initializeFieldBuilder() {
        // Field type selector
        const fieldTypeSelect = document.querySelector('#fieldType');
        if (fieldTypeSelect) {
            fieldTypeSelect.addEventListener('change', (e) => {
                this.updateFieldOptions(e.target.value);
            });
        }

        // Add field button
        const addFieldBtn = document.querySelector('#addField');
        if (addFieldBtn) {
            addFieldBtn.addEventListener('click', () => {
                this.addNewField();
            });
        }
    }

    initializeRelationBuilder() {
        const relationTypeSelect = document.querySelector('#relationType');
        if (relationTypeSelect) {
            relationTypeSelect.addEventListener('change', (e) => {
                this.updateRelationOptions(e.target.value);
            });
        }
    }

    initializeTableBuilder() {
        const addColumnBtn = document.querySelector('#addColumn');
        if (addColumnBtn) {
            addColumnBtn.addEventListener('click', () => {
                this.addNewColumn();
            });
        }
    }

    updateFieldOptions(fieldType) {
        const optionsContainer = document.querySelector('#fieldOptions');
        if (!optionsContainer) return;

        let optionsHTML = '';

        switch(fieldType) {
            case 'string':
                optionsHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Max Length</label>
                            <input type="number" name="maxLength" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Value</label>
                            <input type="text" name="default" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                `;
                break;

            case 'select':
                optionsHTML = `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Options (one per line)</label>
                        <textarea name="options" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="multiple" class="rounded border-gray-300 text-blue-600">
                        <label class="ml-2 text-sm text-gray-700">Allow Multiple</label>
                    </div>
                `;
                break;

            case 'file':
                optionsHTML = `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Upload Directory</label>
                        <input type="text" name="directory" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="image" class="rounded border-gray-300 text-blue-600">
                        <label class="ml-2 text-sm text-gray-700">Image File</label>
                    </div>
                `;
                break;
        }

        optionsContainer.innerHTML = optionsHTML;
    }

    updateRelationOptions(relationType) {
        const optionsContainer = document.querySelector('#relationOptions');
        if (!optionsContainer) return;

        let optionsHTML = '';

        switch(relationType) {
            case 'belongsTo':
                optionsHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Foreign Key</label>
                            <input type="text" name="foreignKey" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Owner Key</label>
                            <input type="text" name="ownerKey" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                `;
                break;

            case 'hasMany':
                optionsHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Foreign Key</label>
                            <input type="text" name="foreignKey" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Local Key</label>
                            <input type="text" name="localKey" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                `;
                break;

            case 'belongsToMany':
                optionsHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pivot Table</label>
                            <input type="text" name="pivotTable" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Foreign Key</label>
                            <input type="text" name="foreignPivotKey" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                `;
                break;
        }

        optionsContainer.innerHTML = optionsHTML;
    }

    addNewField() {
        const fieldList = document.querySelector('#fieldList');
        if (!fieldList) return;

        const fieldId = 'field_' + Date.now();
        const fieldHTML = `
            <div class="field-item bg-gray-50 p-4 rounded-lg mb-3" id="${fieldId}" draggable="true">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="cursor-move">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <div>
                            <input type="text" placeholder="Field Name" class="font-medium border-b border-gray-300 focus:border-blue-500 outline-none">
                            <div class="text-sm text-gray-500">
                                <select class="text-xs border-0 bg-transparent">
                                    <option>Text</option>
                                    <option>Number</option>
                                    <option>Date</option>
                                    <option>Select</option>
                                    <option>File</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="panelBuilder.editField('${fieldId}')" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button onclick="panelBuilder.removeField('${fieldId}')" class="text-red-600 hover:text-red-800">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        fieldList.insertAdjacentHTML('beforeend', fieldHTML);
        this.initializeFieldDragDrop(fieldId);
    }

    addNewColumn() {
        const columnList = document.querySelector('#columnList');
        if (!columnList) return;

        const columnId = 'column_' + Date.now();
        const columnHTML = `
            <div class="column-item bg-gray-50 p-4 rounded-lg mb-3" id="${columnId}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="cursor-move">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <div>
                            <input type="text" placeholder="Column Name" class="font-medium border-b border-gray-300 focus:border-blue-500 outline-none">
                            <div class="text-sm text-gray-500">
                                <select class="text-xs border-0 bg-transparent">
                                    <option>TextColumn</option>
                                    <option>NumberColumn</option>
                                    <option>DateColumn</option>
                                    <option>BooleanColumn</option>
                                    <option>ImageColumn</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="panelBuilder.editColumn('${columnId}')" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button onclick="panelBuilder.removeColumn('${columnId}')" class="text-red-600 hover:text-red-800">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        columnList.insertAdjacentHTML('beforeend', columnHTML);
        this.initializeColumnDragDrop(columnId);
    }

    initializeFieldDragDrop(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        field.draggable = true;

        field.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.target.classList.add('dragging');
        });

        field.addEventListener('dragend', (e) => {
            e.target.classList.remove('dragging');
        });
    }

    initializeColumnDragDrop(columnId) {
        const column = document.getElementById(columnId);
        if (!column) return;

        column.draggable = true;

        column.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.target.classList.add('dragging');
        });

        column.addEventListener('dragend', (e) => {
            e.target.classList.remove('dragging');
        });
    }

    editField(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        // Open field edit modal
        const modal = document.querySelector('#fieldEditModal');
        if (modal) {
            modal.classList.remove('hidden');
            // Populate modal with field data
        }
    }

    removeField(fieldId) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.remove();
        }
    }

    editColumn(columnId) {
        const column = document.getElementById(columnId);
        if (!column) return;

        // Open column edit modal
        const modal = document.querySelector('#columnEditModal');
        if (modal) {
            modal.classList.remove('hidden');
            // Populate modal with column data
        }
    }

    removeColumn(columnId) {
        const column = document.getElementById(columnId);
        if (column) {
            column.remove();
        }
    }

    updateFieldOrder(evt) {
        // Send new order to server
        const fieldIds = Array.from(evt.to.children).map(child => child.id);

        // Livewire event
        if (window.livewire) {
            window.livewire.find('panel-builder').call('updateFieldOrder', fieldIds);
        }
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.field-item:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    handleDrop(e, zone) {
        const fieldType = zone.dataset.fieldType;
        const panelId = zone.dataset.panelId;

        // Add field to panel
        if (window.livewire) {
            window.livewire.find('panel-builder').call('addFieldToPanel', {
                type: fieldType,
                panelId: panelId
            });
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.panelBuilder = new PanelBuilder();
});

// Livewire hooks for dynamic content
document.addEventListener('livewire:init', () => {
    Livewire.hook('component.initialized', (component) => {
        if (component.name === 'panel-builder') {
            // Re-initialize drag and drop after Livewire updates
            setTimeout(() => {
                window.panelBuilder.initializeDragDrop();
            }, 100);
        }
    });
});
