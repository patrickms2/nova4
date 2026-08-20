import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useReactFlow } from '@xyflow/react';

const NOVA_ADDRESS_PREFIX = 'NOVA Address: ';
const CAPABILITY_PREFIX = 'studio.graph.node.capability.';

/**
 * Extracts the NOVA capability id (e.g. "reservations") from a
 * capacidad node's `data.description`, which VoodFlow stores as
 * "NOVA Address: studio.graph.node.capability.reservations".
 *
 * Detection relies on this stable address prefix, never on the
 * translated display label ("Capacidad"/"Reservas"), per mission 030
 * requirement #7.
 */
export function capabilityIdFromDescription(description) {
    if (typeof description !== 'string') return null;
    const address = description.startsWith(NOVA_ADDRESS_PREFIX)
        ? description.slice(NOVA_ADDRESS_PREFIX.length).trim()
        : description.trim();
    if (!address.startsWith(CAPABILITY_PREFIX)) return null;
    const id = address.slice(CAPABILITY_PREFIX.length);
    return id === '' ? null : id;
}

function addressFromDescription(description) {
    if (typeof description !== 'string') return null;
    return description.startsWith(NOVA_ADDRESS_PREFIX)
        ? description.slice(NOVA_ADDRESS_PREFIX.length).trim()
        : null;
}

const BUTTON_COLOR_CLASSES = {
    emerald: 'bg-emerald-500 hover:bg-emerald-600 border-emerald-300',
    blue: 'bg-blue-500 hover:bg-blue-600 border-blue-300',
    amber: 'bg-amber-500 hover:bg-amber-600 border-amber-300',
    slate: 'bg-slate-500 hover:bg-slate-600 border-slate-300',
};

/**
 * Contextual "Add to <Capability>" menu.
 *
 * Resolves compatible Actions/Resources from the canonical NOVA
 * Definition (via /nova/graph/capability-options, backed by
 * CapabilityNodeOptions -> WorkspaceModel), never hardcoded here.
 * Creating an item reuses VoodFlow's existing `createGenericNode`
 * Livewire endpoint so persistence stays on the single existing path.
 */
const CapabilityAddMenu = ({ capabilityNodeId, capabilityLabel, capabilityId, color }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [options, setOptions] = useState(null);
    const containerRef = useRef(null);
    const { getNode, getNodes, getEdges } = useReactFlow();

    const close = useCallback(() => setIsOpen(false), []);

    useEffect(() => {
        if (!isOpen) return undefined;

        const onKeyDown = (e) => {
            if (e.key === 'Escape') close();
        };
        const onClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                close();
            }
        };

        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onClickOutside);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onClickOutside);
        };
    }, [isOpen, close]);

    useEffect(() => {
        if (!isOpen || !capabilityId) return;
        let cancelled = false;
        setLoading(true);
        setError(null);

        fetch(`/nova/graph/capability-options?capability=${encodeURIComponent(capabilityId)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((res) => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then((data) => {
                if (!cancelled) setOptions(data);
            })
            .catch((err) => {
                if (!cancelled) setError(err.message || 'Failed to load options');
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [isOpen, capabilityId]);

    const alreadyAddedAddresses = React.useMemo(() => {
        if (!isOpen) return new Set();
        const nodesById = new Map(getNodes().map((n) => [n.id, n]));
        const addresses = new Set();
        getEdges().forEach((edge) => {
            if (edge.source !== capabilityNodeId) return;
            const target = nodesById.get(edge.target);
            const address = addressFromDescription(target?.data?.description);
            if (address) addresses.add(address);
        });
        return addresses;
    }, [isOpen, getNodes, getEdges, capabilityNodeId]);

    const addItem = useCallback((kind, item) => {
        const livewireId = getNode(capabilityNodeId)?.data?.livewireId;
        if (!livewireId || !window.Livewire) return;
        const comp = window.Livewire.find(livewireId);
        if (!comp) return;

        const parent = getNode(capabilityNodeId);
        const parentPos = parent?.position || { x: 0, y: 0 };
        const siblingsOfKind = getEdges().filter((e) => e.source === capabilityNodeId).length;
        const verticalLane = kind === 'action' ? -180 : 180;

        comp.call('createGenericNode', {
            type: kind === 'action' ? 'acciones' : 'recurso',
            sourceNodeId: capabilityNodeId,
            position: {
                x: parentPos.x + 400,
                y: parentPos.y + verticalLane + siblingsOfKind * 40,
            },
            config: {
                label: item.label,
                description: `${NOVA_ADDRESS_PREFIX}${item.address}`,
                color: kind === 'action' ? 'amber' : 'blue',
            },
        });

        close();
    }, [capabilityNodeId, getNode, getEdges, close]);

    return (
        <div ref={containerRef} className="relative nodrag">
            <button
                type="button"
                onClick={() => setIsOpen((v) => !v)}
                title={`Add to ${capabilityLabel}`}
                className={`flex items-center justify-center w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 text-white shadow-md transition-colors ${
                    BUTTON_COLOR_CLASSES[color] || BUTTON_COLOR_CLASSES.slate
                }`}
            >
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4" />
                </svg>
            </button>

            {isOpen && (
                <div className="nodrag absolute right-0 top-full mt-2 w-64 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl z-50 overflow-hidden">
                    <div className="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                        <div className="text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Add to
                        </div>
                        <div className="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">
                            {capabilityLabel}
                        </div>
                    </div>

                    {loading && (
                        <div className="px-3 py-4 text-xs text-slate-400 italic">Loading…</div>
                    )}

                    {error && !loading && (
                        <div className="px-3 py-4 text-xs text-red-500">{error}</div>
                    )}

                    {!loading && !error && options && (
                        <div className="max-h-80 overflow-y-auto">
                            <MenuSection
                                title="Acciones"
                                items={options.actions}
                                addedAddresses={alreadyAddedAddresses}
                                onSelect={(item) => addItem('action', item)}
                                emptyLabel="No compatible actions"
                            />
                            <MenuSection
                                title="Recursos"
                                items={options.resources}
                                addedAddresses={alreadyAddedAddresses}
                                onSelect={(item) => addItem('resource', item)}
                                emptyLabel="No compatible resources"
                            />
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

function MenuSection({ title, items, addedAddresses, onSelect, emptyLabel }) {
    return (
        <div className="py-1.5">
            <div className="px-3 py-1 text-[10px] font-black uppercase tracking-widest text-slate-400">
                {title}
            </div>
            {items.length === 0 && (
                <div className="px-3 pb-2 text-xs text-slate-400 italic">{emptyLabel}</div>
            )}
            {items.map((item) => {
                const added = addedAddresses.has(item.address);
                return (
                    <button
                        type="button"
                        key={item.address}
                        disabled={added}
                        onClick={() => onSelect(item)}
                        className={`w-full flex items-center justify-between px-3 py-1.5 text-xs text-left transition-colors ${
                            added
                                ? 'text-slate-400 cursor-default'
                                : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer'
                        }`}
                    >
                        <span>{item.label}</span>
                        <span>{added ? '✓' : '+'}</span>
                    </button>
                );
            })}
        </div>
    );
}

export default CapabilityAddMenu;
