import React, { useState, useCallback, useMemo } from 'react';
import ReactFlow, {
    addEdge,
    Background,
    Controls,
    MiniMap,
    useNodesState,
    useEdgesState,
    Panel,
    NodeToolbar,
    Handle,
    Position,
    MarkerType,
} from 'reactflow';
import 'reactflow/dist/style.css';

const defaultPresentations = {
    wa: false,
    filament: false,
    bot: false,
    app: false,
    mcp: false,
};

const getActivePresentations = (presentations) => {
    if (!presentations) return [];
    return Object.entries(presentations)
        .filter(([, enabled]) => enabled)
        .map(([key]) => key.toUpperCase());
};

// Reusable action bar for every node
const NodeActionBar = ({ data }) => (
    <NodeToolbar position="right">
        <button className="node-action-btn info" onClick={() => data.onInfo?.(data.id)} title="Ampliar info">
            ℹ️ Info
        </button>
        <button className="node-action-btn" onClick={() => data.onEdit?.(data.id)} title="Editar">
            ✏️ Edit
        </button>
        <button className="node-action-btn danger" onClick={() => data.onDelete?.(data.id)} title="Eliminar">
            🗑️ Delete
        </button>
    </NodeToolbar>
);

// Custom Node Components
const ModelNode = ({ data, selected }) => {
    return (
        <div className={`model-node ${selected ? 'selected' : ''}`}>
            <div className="node-header">
                <span className="node-icon">📊</span>
                <span className="node-title">{data.label}</span>
            </div>
            <div className="node-content">
                <div className="model-fields">
                    {data.fields?.map((field, index) => (
                        <div key={index} className="field-item">
                            <span className="field-type">{field.type}</span>
                            <span className="field-name">{field.name}</span>
                        </div>
                    ))}
                </div>
            </div>
            <NodeActionBar data={data} />
        </div>
    );
};

const ResourceNode = ({ data, selected }) => {
    return (
        <div className={`resource-node ${selected ? 'selected' : ''}`}>
            <div className="node-header">
                <span className="node-icon">🎛️</span>
                <span className="node-title">{data.label}</span>
            </div>
            <div className="node-content">
                <div className="resource-info">
                    <span className="resource-type">{data.resourceType}</span>
                    <span className="resource-model">{data.modelName}</span>
                </div>
            </div>
            <NodeActionBar data={data} />
        </div>
    );
};

const TableNode = ({ data, selected }) => {
    return (
        <div className={`table-node ${selected ? 'selected' : ''}`}>
            <div className="node-header">
                <span className="node-icon">📋</span>
                <span className="node-title">{data.label}</span>
            </div>
            <div className="node-content">
                <div className="table-columns">
                    {data.columns?.map((column, index) => (
                        <div key={index} className="column-item">
                            <span className="column-name">{column.name}</span>
                            <span className="column-type">{column.type}</span>
                        </div>
                    ))}
                </div>
            </div>
            <NodeActionBar data={data} />
        </div>
    );
};

const FieldNode = ({ data, selected }) => {
    return (
        <div className={`field-node ${selected ? 'selected' : ''}`}>
            <div className="node-header">
                <span className="node-icon">{data.icon}</span>
                <span className="node-title">{data.label}</span>
            </div>
            <div className="node-content">
                <div className="field-details">
                    <span className="field-type">{data.type}</span>
                    <span className="field-validation">{data.required ? 'Required' : 'Optional'}</span>
                </div>
            </div>
            <NodeActionBar data={data} />
        </div>
    );
};

// NOVA Graph semantic node types
const CapabilityNode = ({ data, selected }) => (
    <div className={`capability-node nova-node ${selected ? 'selected' : ''}`}>
        <Handle type="target" position={Position.Left} />
        <div className="node-header">
            <span className="node-icon">🧩</span>
            <span className="node-title">{data.label}</span>
        </div>
        <div className="node-content">
            <span className="nova-type">Capability</span>
            {data.description && <p className="nova-description">{data.description}</p>}
            <div className="presentation-badges">
                {getActivePresentations(data.presentations).map(p => (
                    <span key={p} className="presentation-badge">{p}</span>
                ))}
            </div>
        </div>
        <Handle type="source" position={Position.Right} />
        <NodeActionBar data={data} />
    </div>
);

const ActionNode = ({ data, selected }) => (
    <div className={`action-node nova-node ${selected ? 'selected' : ''}`}>
        <Handle type="target" position={Position.Left} />
        <div className="node-header">
            <span className="node-icon">⚡</span>
            <span className="node-title">{data.label}</span>
        </div>
        <div className="node-content">
            <span className="nova-type">Action</span>
            {data.description && <p className="nova-description">{data.description}</p>}
            <div className="presentation-badges">
                {getActivePresentations(data.presentations).map(p => (
                    <span key={p} className="presentation-badge">{p}</span>
                ))}
            </div>
        </div>
        <Handle type="source" position={Position.Right} />
        <NodeActionBar data={data} />
    </div>
);

const ConnectorNode = ({ data, selected }) => (
    <div className={`connector-node nova-node ${selected ? 'selected' : ''}`}>
        <Handle type="target" position={Position.Left} />
        <div className="node-header">
            <span className="node-icon">🔌</span>
            <span className="node-title">{data.label}</span>
        </div>
        <div className="node-content">
            <span className="nova-type">Connector</span>
            {data.source && <p className="nova-description">{data.source}</p>}
        </div>
        <Handle type="source" position={Position.Right} />
        <NodeActionBar data={data} />
    </div>
);

const TargetNode = ({ data, selected }) => (
    <div className={`target-node nova-node ${selected ? 'selected' : ''}`}>
        <Handle type="target" position={Position.Left} />
        <div className="node-header">
            <span className="node-icon">🎯</span>
            <span className="node-title">{data.label}</span>
        </div>
        <div className="node-content">
            <span className="nova-type">Target</span>
            <div className="presentation-badges">
                {getActivePresentations(data.presentations).map(p => (
                    <span key={p} className="presentation-badge">{p}</span>
                ))}
            </div>
        </div>
        <Handle type="source" position={Position.Right} />
        <NodeActionBar data={data} />
    </div>
);

// Main Panel Builder Component
const PanelFlowBuilder = ({ panelData, onSave, onGenerateCode }) => {
    const [nodes, setNodes, onNodesChange] = useNodesState([]);
    const [edges, setEdges, onEdgesChange] = useEdgesState([]);
    const [selectedNode, setSelectedNode] = useState(null);
    const [isEditing, setIsEditing] = useState(false);
    const [editingData, setEditingData] = useState(null);
    const [infoNode, setInfoNode] = useState(null);
    const [showAddModal, setShowAddModal] = useState(false);
    const [addNodeCategory, setAddNodeCategory] = useState('actions');

    // Define node types
    const nodeTypes = useMemo(() => ({
        model: ModelNode,
        resource: ResourceNode,
        table: TableNode,
        field: FieldNode,
        capability: CapabilityNode,
        action: ActionNode,
        connector: ConnectorNode,
        target: TargetNode,
    }), []);

    // Initialize nodes from panel data
    const initializeNodes = useCallback(() => {
        const initialNodes = [];
        const initialEdges = [];
        let nodeId = 0;

        // Create model node
        if (panelData?.model) {
            initialNodes.push({
                id: `model-${nodeId}`,
                type: 'model',
                position: { x: 250, y: 50 },
                data: {
                    label: panelData.model.name,
                    fields: panelData.model.fields || [],
                    onEdit: handleEditNode,
                    onDelete: handleDeleteNode,
                },
            });
            const modelId = `model-${nodeId}`;
            nodeId++;
        }

        // Create resource node
        if (panelData?.resource) {
            initialNodes.push({
                id: `resource-${nodeId}`,
                type: 'resource',
                position: { x: 500, y: 50 },
                data: {
                    label: panelData.resource.name,
                    resourceType: panelData.resource.type,
                    modelName: panelData.model?.name || '',
                    onEdit: handleEditNode,
                    onDelete: handleDeleteNode,
                },
            });
            const resourceId = `resource-${nodeId}`;

            // Connect model to resource
            if (initialNodes.length > 1) {
                initialEdges.push({
                    id: `edge-${modelId}-${resourceId}`,
                    source: modelId,
                    target: resourceId,
                    type: 'smoothstep',
                    markerEnd: { type: MarkerType.ArrowClosed },
                });
            }
            nodeId++;
        }

        // Create table nodes
        if (panelData?.tables) {
            panelData.tables.forEach((table, index) => {
                const tableId = `table-${nodeId}`;
                initialNodes.push({
                    id: tableId,
                    type: 'table',
                    position: { x: 750, y: 50 + (index * 150) },
                    data: {
                        label: table.name,
                        columns: table.columns || [],
                        onEdit: handleEditNode,
                        onDelete: handleDeleteNode,
                    },
                });

                // Connect resource to table
                if (initialNodes.length > 1) {
                    const resourceId = initialNodes.find(n => n.type === 'resource')?.id;
                    if (resourceId) {
                        initialEdges.push({
                            id: `edge-${resourceId}-${tableId}`,
                            source: resourceId,
                            target: tableId,
                            type: 'smoothstep',
                            markerEnd: { type: MarkerType.ArrowClosed },
                        });
                    }
                }
                nodeId++;
            });
        }

        // Create field nodes
        if (panelData?.fields) {
            panelData.fields.forEach((field, index) => {
                const fieldId = `field-${nodeId}`;
                initialNodes.push({
                    id: fieldId,
                    type: 'field',
                    position: { x: 50, y: 50 + (index * 80) },
                    data: {
                        label: field.label || field.name,
                        type: field.type,
                        icon: getFieldIcon(field.type),
                        required: field.required || false,
                        onEdit: handleEditNode,
                        onDelete: handleDeleteNode,
                    },
                });

                // Connect field to model
                const modelId = initialNodes.find(n => n.type === 'model')?.id;
                if (modelId) {
                    initialEdges.push({
                        id: `edge-${fieldId}-${modelId}`,
                        source: fieldId,
                        target: modelId,
                        type: 'smoothstep',
                        markerEnd: { type: MarkerType.ArrowClosed },
                    });
                }
                nodeId++;
            });
        }

        setNodes(initialNodes);
        setEdges(initialEdges);
    }, [panelData, setNodes, setEdges]);

    // Initialize on mount and when panel data changes
    React.useEffect(() => {
        if (panelData) {
            initializeNodes();
        }
    }, [panelData, initializeNodes]);

    // Handle connections
    const onConnect = useCallback((params) => {
        const newEdge = {
            ...params,
            type: 'smoothstep',
            markerEnd: { type: MarkerType.ArrowClosed },
        };
        setEdges((eds) => addEdge(newEdge, eds));
    }, [setEdges]);

    // Handle editing
    const handleEditNode = useCallback((nodeId) => {
        const node = nodes.find(n => n.id === nodeId);
        if (node) {
            setSelectedNode(node);
            setEditingData({
                ...node.data,
                presentations: node.data.presentations || { ...defaultPresentations },
            });
            setIsEditing(true);
        }
    }, [nodes]);

    const presentationOptions = [
        { key: 'wa', label: 'WhatsApp' },
        { key: 'filament', label: 'Filament' },
        { key: 'bot', label: 'Bot' },
        { key: 'app', label: 'App' },
        { key: 'mcp', label: 'MCP' },
    ];

    // Handle node deletion
    const handleDeleteNode = useCallback((nodeId) => {
        setNodes((nds) => nds.filter(n => n.id !== nodeId));
        setEdges((eds) => eds.filter(e => e.source !== nodeId && e.target !== nodeId));
    }, [setNodes, setEdges]);

    // Handle adding new nodes
    const handleAddNode = useCallback((nodeType) => {
        const typeLabels = {
            model: 'Model',
            resource: 'Resource',
            table: 'Table',
            field: 'Field',
            capability: 'Capability',
            action: 'Action',
            connector: 'Connector',
            target: 'Target',
        };

        const newNode = {
            id: `${nodeType}-${Date.now()}`,
            type: nodeType,
            position: { x: Math.random() * 400 + 100, y: Math.random() * 300 + 100 },
            data: {
                label: `New ${typeLabels[nodeType] || nodeType}`,
                presentations: { ...defaultPresentations },
                onEdit: handleEditNode,
                onInfo: handleInfoNode,
                onDelete: handleDeleteNode,
                ...(nodeType === 'model' && { fields: [] }),
                ...(nodeType === 'resource' && { resourceType: 'Resource', modelName: '' }),
                ...(nodeType === 'table' && { columns: [] }),
                ...(nodeType === 'field' && { type: 'text', icon: '📝', required: false }),
                ...(nodeType === 'capability' && { description: '' }),
                ...(nodeType === 'action' && { description: '' }),
                ...(nodeType === 'connector' && { source: '' }),
                ...(nodeType === 'target' && { targetType: 'Filament' }),
            },
        };
        setNodes((nds) => [...nds, newNode]);
        setShowAddModal(false);
    }, [setNodes, handleEditNode, handleInfoNode, handleDeleteNode]);

    // Handle info request
    const handleInfoNode = useCallback((nodeId) => {
        const node = nodes.find(n => n.id === nodeId);
        if (node) {
            setInfoNode(node);
        }
    }, [nodes]);

    // Handle saving edited node
    const handleSaveEdit = useCallback(() => {
        if (selectedNode && editingData) {
            setNodes((nds) =>
                nds.map((node) =>
                    node.id === selectedNode.id
                        ? { ...node, data: { ...node.data, ...editingData } }
                        : node
                )
            );
            setIsEditing(false);
            setSelectedNode(null);
            setEditingData(null);
        }
    }, [selectedNode, editingData, setNodes]);

    // Handle saving the entire flow
    const handleSave = useCallback(() => {
        const flowData = {
            nodes: nodes.map(node => ({
                type: node.type,
                data: node.data,
                position: node.position,
            })),
            edges: edges.map(edge => ({
                source: edge.source,
                target: edge.target,
                type: edge.type,
            })),
        };
        onSave?.(flowData);
    }, [nodes, edges, onSave]);

    // Handle code generation
    const handleGenerateCode = useCallback(() => {
        const flowData = {
            nodes: nodes.map(node => ({
                type: node.type,
                data: node.data,
            })),
            edges: edges.map(edge => ({
                source: edge.source,
                target: edge.target,
            })),
        };
        onGenerateCode?.(flowData);
    }, [nodes, edges, onGenerateCode]);

    return (
        <div className="panel-flow-builder">
            <ReactFlow
                nodes={nodes}
                edges={edges}
                onNodesChange={onNodesChange}
                onEdgesChange={onEdgesChange}
                onConnect={onConnect}
                nodeTypes={nodeTypes}
                fitView
                attributionPosition="bottom-left"
            >
                <Background />
                <Controls />
                <MiniMap />

                {/* Toolbar Panel */}
                <Panel position="top-left">
                    <div className="flow-toolbar">
                        <h3>Panel Builder</h3>
                        <div className="toolbar-buttons">
                            <button onClick={() => handleAddNode('model')}>+ Model</button>
                            <button onClick={() => handleAddNode('resource')}>+ Resource</button>
                            <button onClick={() => handleAddNode('table')}>+ Table</button>
                            <button onClick={() => handleAddNode('field')}>+ Field</button>
                        </div>
                    </div>
                </Panel>

                {/* Actions Panel */}
                <Panel position="top-right">
                    <div className="flow-actions">
                        <button onClick={() => setShowAddModal(true)} className="add-node-btn">
                            ➕ Add Node
                        </button>
                        <button onClick={handleSave} className="save-btn">
                            💾 Save
                        </button>
                        <button onClick={handleGenerateCode} className="generate-btn">
                            ⚡ Generate Code
                        </button>
                    </div>
                </Panel>
            </ReactFlow>

            {/* Info Modal */}
            {infoNode && (
                <div className="edit-modal" onClick={() => setInfoNode(null)}>
                    <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                        <h3>Node Info</h3>
                        <div className="form-group">
                            <label>ID:</label>
                            <p>{infoNode.id}</p>
                        </div>
                        <div className="form-group">
                            <label>Type:</label>
                            <p>{infoNode.type}</p>
                        </div>
                        <div className="form-group">
                            <label>Label:</label>
                            <p>{infoNode.data?.label}</p>
                        </div>
                        <div className="form-group">
                            <label>Active Presentations:</label>
                            <p>{getActivePresentations(infoNode.data?.presentations).join(', ') || 'None'}</p>
                        </div>
                        <div className="modal-actions">
                            <button onClick={() => setInfoNode(null)}>Close</button>
                        </div>
                    </div>
                </div>
            )}

            {/* Add Node Modal */}
            {showAddModal && (
                <div className="edit-modal" onClick={() => setShowAddModal(false)}>
                    <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                        <h3>Add Node</h3>
                        <div className="form-group">
                            <label>Category:</label>
                            <select value={addNodeCategory} onChange={(e) => setAddNodeCategory(e.target.value)}>
                                <option value="actions">Actions</option>
                                <option value="resources">Resources</option>
                                <option value="capabilities">Capabilities</option>
                                <option value="panel">Panel Elements</option>
                            </select>
                        </div>
                        <div className="toolbar-buttons" style={{ marginBottom: '1rem' }}>
                            {addNodeCategory === 'actions' && (
                                <>
                                    <button onClick={() => handleAddNode('action')}>⚡ Action</button>
                                </>
                            )}
                            {addNodeCategory === 'resources' && (
                                <>
                                    <button onClick={() => handleAddNode('resource')}>🎛️ Resource</button>
                                    <button onClick={() => handleAddNode('target')}>🎯 Target</button>
                                    <button onClick={() => handleAddNode('connector')}>🔌 Connector</button>
                                </>
                            )}
                            {addNodeCategory === 'capabilities' && (
                                <>
                                    <button onClick={() => handleAddNode('capability')}>🧩 Capability</button>
                                </>
                            )}
                            {addNodeCategory === 'panel' && (
                                <>
                                    <button onClick={() => handleAddNode('model')}>📊 Model</button>
                                    <button onClick={() => handleAddNode('table')}>📋 Table</button>
                                    <button onClick={() => handleAddNode('field')}>📝 Field</button>
                                </>
                            )}
                        </div>
                        <div className="modal-actions">
                            <button onClick={() => setShowAddModal(false)}>Close</button>
                        </div>
                    </div>
                </div>
            )}

            {/* Edit Modal */}
            {isEditing && (
                <div className="edit-modal" onClick={() => setIsEditing(false)}>
                    <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                        <h3>Edit Node</h3>
                        <div className="form-group">
                            <label>Label:</label>
                            <input
                                type="text"
                                value={editingData?.label || ''}
                                onChange={(e) => setEditingData({ ...editingData, label: e.target.value })}
                            />
                        </div>

                        {selectedNode?.type === 'model' && (
                            <div className="form-group">
                                <label>Model Name:</label>
                                <input
                                    type="text"
                                    value={editingData?.modelName || ''}
                                    onChange={(e) => setEditingData({ ...editingData, modelName: e.target.value })}
                                />
                            </div>
                        )}

                        {(selectedNode?.type === 'capability' || selectedNode?.type === 'action') && (
                            <div className="form-group">
                                <label>Description:</label>
                                <textarea
                                    value={editingData?.description || ''}
                                    onChange={(e) => setEditingData({ ...editingData, description: e.target.value })}
                                    rows="2"
                                />
                            </div>
                        )}

                        {selectedNode?.type === 'field' && (
                            <>
                                <div className="form-group">
                                    <label>Field Type:</label>
                                    <select
                                        value={editingData?.type || 'text'}
                                        onChange={(e) => setEditingData({ ...editingData, type: e.target.value })}
                                    >
                                        <option value="text">Text</option>
                                        <option value="textarea">Textarea</option>
                                        <option value="number">Number</option>
                                        <option value="email">Email</option>
                                        <option value="select">Select</option>
                                        <option value="checkbox">Checkbox</option>
                                        <option value="date">Date</option>
                                        <option value="file">File</option>
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label>
                                        <input
                                            type="checkbox"
                                            checked={editingData?.required || false}
                                            onChange={(e) => setEditingData({ ...editingData, required: e.target.checked })}
                                        />
                                        Required
                                    </label>
                                </div>
                            </>
                        )}

                        <div className="form-group">
                            <label>Presentations (enable in other previews):</label>
                            <div className="presentation-toggles">
                                {presentationOptions.map((opt) => (
                                    <label key={opt.key} className="presentation-toggle">
                                        <input
                                            type="checkbox"
                                            checked={editingData?.presentations?.[opt.key] || false}
                                            onChange={(e) => setEditingData({
                                                ...editingData,
                                                presentations: {
                                                    ...editingData.presentations,
                                                    [opt.key]: e.target.checked,
                                                },
                                            })}
                                        />
                                        {opt.label}
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="modal-actions">
                            <button onClick={() => setIsEditing(false)}>Cancel</button>
                            <button onClick={handleSaveEdit}>Save</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

// Helper function to get field icon
function getFieldIcon(fieldType) {
    const icons = {
        text: '📝',
        textarea: '📄',
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
        richeditor: '📝',
    };
    return icons[fieldType] || '📝';
}

// Export for use in other components
window.PanelFlowBuilder = PanelFlowBuilder;
