import React, { useState, useEffect } from 'react';
import { Position, useReactFlow } from '@xyflow/react';
import {
    BaseNodeContainer,
    NodeHeader,
    NodeConfigReadOnlyNotice,
    NodeNonExecutableNotice,
    CollapsedView,
    NodeConfigFields,
    FieldLabel,
    TextInput,
    SelectInput,
    StandardHandle,
    NODE_CONFIG_DEBOUNCE_MS,
    useStandardNodeBehavior,
    useNodeAccessUi
} from '../../../../vendor/voodflow/voodflow/resources/js/components/nodes';

/**
 * Recurso React Component
 *
 * Custom node for workflow automation
 *
 * @author Voodflow
 * @version 1.0.0
 * @see https://voodflow.com
 */
const Recurso = ({ id, data, selected }) => {
    // ✅ Color defined once at the top - read from manifest via data.color
    const nodeColor = data.color || 'emerald';
    
    const { ConfirmModal, SimpleAddButton, VoodflowLogo } = window.VoodflowCommon || {};
    const { setNodes, getEdges } = useReactFlow();
    const { workflowReadOnly, configReadOnly, canShowDeleteButton } = useNodeAccessUi(data);

    // ✅ Use standard node behavior
    const {
        isExpanded,
        toggleExpansion,
        isConnected,
        isOutputConnected,
        shouldShowLogo,
        hasConfiguration
    } = useStandardNodeBehavior(id, { ...data, defaultLabel: 'Recurso' }, getEdges, setNodes, false);

    // ✅ Form State
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [label, setLabel] = useState(data.label || 'Recurso');
    const [description, setDescription] = useState(data.description || '');
    
    // TODO: Add your custom fields here
    // const [myField, setMyField] = useState(data.myField || '');

    // ✅ Debounced save + ReactFlow update
    useEffect(() => {
        if (configReadOnly) return;
        const hasChanged =
            label !== data.label ||
            description !== data.description;
            // || myField !== data.myField;  // Add your custom fields

        if (!hasChanged) return;

        const timeoutId = setTimeout(() => {
            // Save to backend via Livewire
            if (data.livewireId && window.Livewire) {
                const component = window.Livewire.find(data.livewireId);
                if (component) {
                    component.call('updateNodeConfig', {
                        nodeId: id,
                        label,
                        description,
                        // myField,  // Add your custom fields
                    });
                }
            }

            // Update local ReactFlow state
            setNodes((nds) => nds.map((node) => {
                if (node.id === id) {
                    return {
                        ...node,
                        data: {
                            ...node.data,
                            label,
                            description,
                            // myField,  // Add your custom fields
                        }
                    };
                }
                return node;
            }));
        }, NODE_CONFIG_DEBOUNCE_MS);

        return () => clearTimeout(timeoutId);
    }, [configReadOnly, label, description, data.label, data.description, data.livewireId, id, setNodes]);
    // Add your custom fields to dependencies: myField, data.myField

    const handleDelete = () => {
        if (!canShowDeleteButton) return;
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteNode', id);
        }
    };

    // ✅ Collapsed view helpers
    const getBadgeText = () => {
        // TODO: Return badge text (e.g., "CONFIGURED", "ACTIVE", etc.)
        return null;
    };

    const getCollapsedSummary = () => {
        // TODO: Return summary for collapsed view when configured
        return null;
    };

    return (
        <>
            {canShowDeleteButton && (
                <ConfirmModal
                    isOpen={showDeleteModal}
                    title="Delete Recurso"
                    message={`Are you sure you want to delete "${label}"?`}
                    onConfirm={handleDelete}
                    onCancel={() => setShowDeleteModal(false)}
                />
            )}

            <BaseNodeContainer
                color={nodeColor}
                selected={selected}
                isExpanded={isExpanded}
                minWidth="320px"
                maxWidth="450px"
            >
                <NodeHeader
                    color={nodeColor}
                    icon={data.icon || "M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"}
                    nodeData={data}
                    subtitle={data.display_name?.toUpperCase() || "NODE"}
                    title={label}
                    badge={getBadgeText()}
                    isExpanded={isExpanded}
                    canExpand={true}
                    onToggleExpand={toggleExpansion}
                    onDelete={canShowDeleteButton ? () => setShowDeleteModal(true) : undefined}
                />

                {isExpanded ? (
                    <div className="nodrag">
                        <NodeConfigReadOnlyNotice data={data} />
                        <NodeNonExecutableNotice data={data} />
                        <NodeConfigFields
                            label={label}
                            description={description}
                            onLabelChange={(e) => setLabel(e.target.value)}
                            onDescriptionChange={(e) => setDescription(e.target.value)}
                            labelPlaceholder="Node name"
                            descriptionPlaceholder="Optional description"
                            color={nodeColor}
                            readOnly={configReadOnly}
                        />

                        {/* TODO: Add your custom configuration fields here */}
                        {/* Example:
                        <div className="px-4 pb-4 border-t border-slate-100 dark:border-slate-800 pt-4 space-y-4">
                            <div className="space-y-1">
                                <FieldLabel>My Field</FieldLabel>
                                <TextInput
                                    value={myField}
                                    onChange={(e) => setMyField(e.target.value)}
                                    placeholder="Enter value..."
                                    color={nodeColor}
                                />
                            </div>
                        </div>
                        */}
                    </div>
                ) : (
                    <CollapsedView
                        shouldShowLogo={shouldShowLogo}
                        hasConfiguration={hasConfiguration}
                        description={description}
                        summary={getCollapsedSummary()}
                        VoodflowLogo={VoodflowLogo}
                        isTrigger={false}
                    />
                )}

                <StandardHandle
                    type="target"
                    position={Position.Left}
                    color={nodeColor}
                />
                <StandardHandle
                    type="source"
                    position={Position.Right}
                    color={nodeColor}
                    isConnected={isOutputConnected}
                />

                {!workflowReadOnly && !isOutputConnected && SimpleAddButton && (
                    <div className="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 z-10">
                        <SimpleAddButton
                            onAddNode={(type, sourceId, position) => {
                                if (data.livewireId && window.Livewire) {
                                    window.Livewire.find(data.livewireId).call('createGenericNode', {
                                        type,
                                        sourceNodeId: sourceId,
                                        position
                                    });
                                }
                            }}
                            sourceNodeId={id}
                            livewireId={data.livewireId}
                            availableNodes={data.availableNodes}
                            color={nodeColor}
                        />
                    </div>
                )}
            </BaseNodeContainer>
        </>
    );
};

export default Recurso;
