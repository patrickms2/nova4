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
 * CreateNovaTaskNode React Component
 *
 * Custom node for workflow automation
 *
 * @author Voodflow
 * @version 1.0.0
 * @see https://voodflow.com
 */
const CreateNovaTaskNode = ({ id, data, selected }) => {
    // ✅ Color defined once at the top - read from manifest via data.color
    const nodeColor = data.color || 'blue';

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
    } = useStandardNodeBehavior(id, { ...data, defaultLabel: 'CreateNovaTaskNode' }, getEdges, setNodes, false);

    const priorityOptions = [
        { value: 'low', label: 'Low' },
        { value: 'medium', label: 'Medium' },
        { value: 'high', label: 'High' },
    ];

    // ✅ Form State
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [label, setLabel] = useState(data.label || 'CreateNovaTaskNode');
    const [description, setDescription] = useState(data.description || '');
    const [title, setTitle] = useState(data.title || '');
    const [descriptionField, setDescriptionField] = useState(data.description_field || '');
    const [dueDate, setDueDate] = useState(data.due_date || '');
    const [estimatedHours, setEstimatedHours] = useState(data.estimated_hours || '1');
    const [priority, setPriority] = useState(data.priority || 'medium');

    // ✅ Debounced save + ReactFlow update
    useEffect(() => {
        if (configReadOnly) return;
        const hasChanged =
            label !== data.label ||
            description !== data.description ||
            title !== data.title ||
            descriptionField !== data.description_field ||
            dueDate !== data.due_date ||
            estimatedHours !== data.estimated_hours ||
            priority !== data.priority;

        if (!hasChanged) return;

        const timeoutId = setTimeout(() => {
            const payload = {
                nodeId: id,
                label,
                description,
                title,
                description_field: descriptionField,
                due_date: dueDate,
                estimated_hours: estimatedHours,
                priority,
            };

            // Save to backend via Livewire
            if (data.livewireId && window.Livewire) {
                const component = window.Livewire.find(data.livewireId);
                if (component) {
                    component.call('updateNodeConfig', payload);
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
                            title,
                            description_field: descriptionField,
                            due_date: dueDate,
                            estimated_hours: estimatedHours,
                            priority,
                        }
                    };
                }
                return node;
            }));
        }, NODE_CONFIG_DEBOUNCE_MS);

        return () => clearTimeout(timeoutId);
    }, [
        configReadOnly, label, description, title, descriptionField, dueDate, estimatedHours, priority,
        data.label, data.description, data.title, data.description_field, data.due_date, data.estimated_hours, data.priority,
        data.livewireId, id, setNodes,
    ]);

    const handleDelete = () => {
        if (!canShowDeleteButton) return;
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteNode', id);
        }
    };

    // ✅ Collapsed view helpers
    const getBadgeText = () => {
        return title ? 'CONFIGURED' : null;
    };

    const getCollapsedSummary = () => {
        if (!title) return null;

        return `${title} · ${dueDate || 'sin fecha'} · ${priority}`;
    };

    return (
        <>
            {canShowDeleteButton && (
                <ConfirmModal
                    isOpen={showDeleteModal}
                    title="Delete CreateNovaTaskNode"
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
                    icon={data.icon || "M12 19l9 2-9-18-9 18 9-2zm0 0v-8"}
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

                        <div className="px-4 pb-4 border-t border-slate-100 dark:border-slate-800 pt-4 space-y-4">
                            <div className="space-y-1">
                                <FieldLabel>Title</FieldLabel>
                                <TextInput
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    placeholder="{{trigger.title}}"
                                    color={nodeColor}
                                    readOnly={configReadOnly}
                                />
                            </div>
                            <div className="space-y-1">
                                <FieldLabel>Description</FieldLabel>
                                <TextInput
                                    value={descriptionField}
                                    onChange={(e) => setDescriptionField(e.target.value)}
                                    placeholder="Optional"
                                    color={nodeColor}
                                    readOnly={configReadOnly}
                                />
                            </div>
                            <div className="space-y-1">
                                <FieldLabel>Due Date</FieldLabel>
                                <TextInput
                                    value={dueDate}
                                    onChange={(e) => setDueDate(e.target.value)}
                                    placeholder="{{trigger.due_date}} or 2026-08-10"
                                    color={nodeColor}
                                    readOnly={configReadOnly}
                                />
                            </div>
                            <div className="space-y-1">
                                <FieldLabel>Estimated Hours</FieldLabel>
                                <TextInput
                                    value={estimatedHours}
                                    onChange={(e) => setEstimatedHours(e.target.value)}
                                    placeholder="1"
                                    color={nodeColor}
                                    readOnly={configReadOnly}
                                />
                            </div>
                            <div className="space-y-1">
                                <FieldLabel>Priority</FieldLabel>
                                <SelectInput
                                    value={priority}
                                    onChange={(e) => setPriority(e.target.value)}
                                    options={priorityOptions}
                                    color={nodeColor}
                                    disabled={configReadOnly}
                                />
                            </div>
                        </div>
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

export default CreateNovaTaskNode;
