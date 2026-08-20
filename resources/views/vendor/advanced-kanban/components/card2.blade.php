@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Filament\Support\Icons\Heroicon;

    //dd($record);
@endphp
{{-- TaskResource Kanban Card Component --}}
{{-- This component is based on the Advanced Kanban package card --}}
{{-- You can customize this component to display your model's data --}}

@props([
    'record',
    'lockedColumn',
    'actions' => []
])

<div x-data="{ visible: false, toggley(){this.visible =! this.visible}}"
>

    {{-- Tickets Display --}}
    <div wire:loading.class="opacity-50 pointer-events-none"
         wire:target="search, status, priority, assignedTo, clientId, perPage, sortBy">
        @if($viewMode === 'cards')
            {{-- Cards View --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($tickets as $ticket)
                    @php
                        $priorityColor = \App\Helpers\StatusColorHelper::toHex(
                            \App\Helpers\StatusColorHelper::priority($ticket->priority)
                        );
                    @endphp
                    <div
                        wire:key="ticket-{{ $ticket->id }}"
                        x-data="{ isUpdating: false }"
                        @ticket-updating.window="if ($event.detail.id == {{ $ticket->id }}) isUpdating = true; setTimeout(() => isUpdating = false, 1000)"
                        :class="{ 'animate-pulse': isUpdating }"
                    >
                        <flux:card
                            class="relative group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer"
                            style="border-left: 4px solid {{ $priorityColor }}"
                            onclick="window.location.href='{{ route('tickets.show', $ticket) }}'"
                        >
                            {{-- Selection Checkbox --}}
                            <div class="absolute top-3 left-3 z-10" @click.stop>
                                <flux:checkbox
                                    wire:model.live="selectedTickets"
                                    value="{{ $ticket->id }}"
                                />
                            </div>

                            {{-- Card Content --}}
                            <div class="p-4">
                                {{-- Header with Ticket Number and Actions --}}
                                <div class="flex items-start justify-between mb-3">
                        <span class="text-blue-600 font-semibold">
                            #{{ $ticket->number }}
                        </span>
                                    <div role="presentation" @click.stop>
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm"
                                                         class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <flux:icon.ellipsis-vertical class="size-4"/>
                                            </flux:button>
                                            <flux:menu>
                                                <flux:menu.item href="{{ route('tickets.show', $ticket) }}">
                                                    <flux:icon.eye class="size-4"/>
                                                    View
                                                </flux:menu.item>
                                                <flux:menu.item href="{{ route('tickets.edit', $ticket) }}">
                                                    <flux:icon.pencil class="size-4"/>
                                                    Edit
                                                </flux:menu.item>
                                                <flux:menu.separator/>
                                                <flux:menu.item
                                                    wire:click="deleteTicket({{ $ticket->id }})"
                                                    wire:confirm="Are you sure you want to archive this ticket?"
                                                    variant="danger"
                                                >
                                                    <flux:icon.trash class="size-4"/>
                                                    Archive
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>

                                {{-- Subject --}}
                                <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    {{ Str::limit($ticket->subject, 50) }}
                                </h3>

                                {{-- Description Preview --}}
                                @if($ticket->description)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                        {{ Str::limit($ticket->description, 80) }}
                                    </p>
                                @endif

                                {{-- Badges Row --}}
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @if($ticket->is_internal)
                                        <flux:badge color="amber" size="sm">Internal</flux:badge>
                                    @endif
                                    @includeIf('filament.tables.columns.status-badge', ['model' => $ticket, 'status' => $ticket->status])
                                    @includeIf('filament.tables.columns.priority-badge', ['model' => $ticket, 'priority' => $ticket->priority])
                                </div>

                                {{-- Meta Information --}}
                                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{-- Client --}}
                                    <div class="flex items-center gap-2">
                                        <flux:icon.building-office class="size-4"/>
                                        @if($ticket->is_internal)
                                            <span class="text-amber-600 dark:text-amber-400 font-medium">Internal</span>
                                        @else
                                            <span>{{ $ticket->client?->name ?? 'No client' }}</span>
                                        @endif
                                    </div>

                                    {{-- Assignee --}}
                                    <div class="flex items-center gap-2">
                                        <flux:icon.user class="size-4"/>
                                        <span>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</span>
                                    </div>

                                    {{-- Created Date --}}
                                    <div class="flex items-center gap-2">
                                        <flux:icon.clock class="size-4"/>
                                        <span>{{ $ticket->created_at?->format('M d, Y g:i A') ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Quick Actions Bar (appears on hover) --}}
                                <div
                                    class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-white dark:from-gray-900 to-transparent p-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <div class="flex gap-2 justify-center" onclick="event.stopPropagation()">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            href="{{ route('tickets.show', $ticket) }}"
                                            class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm"
                                        >
                                            <flux:icon.eye class="size-4"/>
                                            View
                                        </flux:button>
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            href="{{ route('tickets.edit', $ticket) }}"
                                            class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm"
                                        >
                                            <flux:icon.pencil class="size-4"/>
                                            Edit
                                        </flux:button>
                                        @if($ticket->status !== 'closed')
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                wire:click="updateStatus({{ $ticket->id }}, 'closed')"
                                                class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm"
                                            >
                                                <flux:icon.check class="size-4"/>
                                                Close
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </flux:card>
                    </div>
                @empty
                    <div class="col-span-full">
                        <flux:card>
                            <div class="text-center py-12">
                                <flux:icon.ticket class="size-12 mx-auto mb-4 text-gray-300"/>
                                <p class="text-gray-500">No tickets found</p>
                            </div>
                        </flux:card>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Table View --}}
            <flux:card>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>
                            <flux:checkbox wire:model.live="selectAll"/>
                        </flux:table.column>
                        <flux:table.column
                            sortable
                            wire:click="sortBy('number')"
                            class="cursor-pointer"
                        >
                            Ticket #
                            @if($sortField === 'number')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3 inline" />
                                @else
                                    <flux:icon.chevron-down class="size-3 inline" />
                                @endif
                            @endif
                        </flux:table.column>
                        <flux:table.column
                            sortable
                            wire:click="sortBy('subject')"
                            class="cursor-pointer"
                        >
                            Subject
                            @if($sortField === 'subject')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3 inline" />
                                @else
                                    <flux:icon.chevron-down class="size-3 inline" />
                                @endif
                            @endif
                        </flux:table.column>
                        <flux:table.column>Client</flux:table.column>
                        <flux:table.column
                            sortable
                            wire:click="sortBy('status')"
                            class="cursor-pointer"
                        >
                            Status
                            @if($sortField === 'status')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3 inline" />
                                @else
                                    <flux:icon.chevron-down class="size-3 inline" />
                                @endif
                            @endif
                        </flux:table.column>
                        <flux:table.column
                            sortable
                            wire:click="sortBy('priority')"
                            class="cursor-pointer"
                        >
                            Priority
                            @if($sortField === 'priority')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3 inline" />
                                @else
                                    <flux:icon.chevron-down class="size-3 inline" />
                                @endif
                            @endif
                        </flux:table.column>
                        <flux:table.column>Assigned To</flux:table.column>
                        <flux:table.column
                            sortable
                            wire:click="sortBy('created_at')"
                            class="cursor-pointer"
                        >
                            Created
                            @if($sortField === 'created_at')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-3 inline" />
                                @else
                                    <flux:icon.chevron-down class="size-3 inline" />
                                @endif
                            @endif
                        </flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($tickets as $ticket)
                            <flux:table.row
                                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                                onclick="window.location.href='{{ route('tickets.show', $ticket) }}'"
                            >
                                <flux:table.cell onclick="event.stopPropagation()">
                                    <flux:checkbox
                                        wire:model.live="selectedTickets"
                                        value="{{ $ticket->id }}"
                                    />
                                </flux:table.cell>
                                <flux:table.cell>
                                <span class="text-blue-600 font-medium">
                                    #{{ $ticket->number }}
                                </span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div>
                                        <div class="font-medium">{{ Str::limit($ticket->subject, 50) }}</div>
                                        @if($ticket->description)
                                            <div
                                                class="text-sm text-gray-500">{{ Str::limit($ticket->description, 60) }}</div>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($ticket->is_internal)
                                        <span class="text-amber-600 dark:text-amber-400 font-medium">Internal</span>
                                    @else
                                        {{ $ticket->client?->name ?? '-' }}
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-1">
                                        @if($ticket->is_internal)
                                            <flux:badge color="amber" size="sm">INT</flux:badge>
                                        @endif
                                        @includeIf('filament.tables.columns.status-badge', ['model' => $ticket, 'status' => $ticket->status])
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @includeIf('filament.tables.columns.priority-badge', ['model' => $ticket, 'priority' => $ticket->priority])
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div>
                                        {{ $ticket->created_at?->format('M d, Y') ?? '-' }}
                                        <div class="text-sm text-gray-500">
                                            {{ $ticket->created_at?->format('g:i A') ?? '' }}
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell onclick="event.stopPropagation()">
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm">
                                            <flux:icon.ellipsis-horizontal class="size-4"/>
                                        </flux:button>
                                        <flux:menu>
                                            <flux:menu.item href="{{ route('tickets.show', $ticket) }}">
                                                <flux:icon.eye class="size-4"/>
                                                View
                                            </flux:menu.item>
                                            <flux:menu.item href="{{ route('tickets.edit', $ticket) }}">
                                                <flux:icon.pencil class="size-4"/>
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.separator/>
                                            <flux:menu.item
                                                wire:click="deleteTicket({{ $ticket->id }})"
                                                wire:confirm="Are you sure you want to archive this ticket?"
                                                variant="danger"
                                            >
                                                <flux:icon.trash class="size-4"/>
                                                Archive
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="9" class="text-center py-8">
                                    <div class="text-gray-500">
                                        <flux:icon.ticket class="size-12 mx-auto mb-4 text-gray-300"/>
                                        <p>No tickets found</p>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>
    <flux:callout variant="secondary" x-data="{ show: true }"
                  class="bg-white m-0 p-0 no-border">
        <x-slot name="controls" x-show="{show}"
                x-on:hover="show = false"
                class="ps-4 pr-2 flex-col justify-center w-8 py-2 items-top gap-2">

            <x-filament::button variant=primary
                                size="xs"
                                color="danger" @click="toggley()" x-show="!visible"
                                icon="heroicon-o-chevron-down"/>
            <x-filament::button
                size="xs" variant="primary" color="danger"
                @click="toggley()" x-show="visible" icon="heroicon-o-x-mark"/>
            @foreach($actions as $action)
                {{ $action }}
            @endforeach
        </x-slot>
        <x-slot name="heading" class="  py-0  no-border">

            <flux:callout.heading
                class="uppercase  no-border  py-0  pt-1 m-0  flex w-full rounded-lg  justify-center "
                color="red" size="xl">
                <div class="kanban-item-header flex-col py-0  no-border"

                ><h4 class="kanban-item-title" @click="toggley()"
                    >
                        {{ $record->{$this->getKanban()->getTitleField()} }}
                    </h4>
            </flux:callout.heading>
        </x-slot>


</div>
<div wire:click="mountAction('viewAction', {recordId: {{ $record->id }} })">
    <div class="flex items-center justify-between gap-2 mt-2 w-full">
        @if($name = $record->departamento?->nombre)
            <div class="flex items-center gap-2">
                <x-filament::avatar
                    :size="Size::Small->value"
                    src="https://api.dicebear.com/9.x/adventurer/svg?seed={{ $name }}"
                    alt="Advanced Kanban"
                />
                <span class="text-xs">{{ $name }}</span>
            </div>
        @else
            <div class="kanban-item-footer">
                @if($lockedColumn->isCardLocked)
                    <div class="flex items-center gap-x-1 bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded-full">
                        <x-filament::icon
                            :icon=" $lockedColumn->icon"
                            class="h-3 w-3 text-gray-500 dark:text-gray-200"
                        />
                        <span
                            class="!text-xs font-medium text-gray-500 dark:text-gray-200">{{ $lockedColumn->label }}</span>
                    </div>
                @endif
            </div>
        @endif
        <div class="flex shrink-0 items-center gap-x-2">
            <x-filament::icon
                :icon="Heroicon::Calendar"
                :size="IconSize::Small"
                class="text-zinc-500"
            />
            <span class="text-xs">{{ $record->appointment_date?->format('M d, Y') }}</span>
        </div>
    </div>
</div>


</flux:callout>
<div class="kanban-item-description text-sm text-zinc-500 dark:text-zinc-400">
    <div
        x-show="visible"
        class="flex-col mt-4 p-4 bg-red-50 dark:bg-red-900/20  no-border border-red-200 dark:border-red-800 rounded-lg">
        <div class="text-sm text-red-800 dark:text-red-300">
            <flux:heading size="md">Detalles de la Cita :</flux:heading>
            @if($record->{$this->getKanban()->getDescriptionField()})
                <p class="kanban-item-description">
                    {{ Str::limit($record->{$this->getKanban()->getDescriptionField()}, 80) }}
                </p>
                <div>
                    <x-filament::badge
                        size="sm"
                        :color="$record->status->getColor()"
                        :icon="$record->status->getIcon()"
                    >
                        {{ $record->status->getLabel() }}
                    </x-filament::badge>
                </div>
            @endif

            <div class="mt-1 px-2">
                <strong>Usuario:</strong> {{ $record->usuario->nombre }}<br>
                <strong>Departamento:</strong> {{ $record->departamento->nombre }}<br>

                <strong>Fecha y
                    Hora:</strong> {{ $record->appointment_date->format('d/m/Y')  }} {{ $record->slot_id  }}
                <br>
                <strong>Motivo:</strong> {{ $record->appointment_type }}
                <strong>Estado:</strong> {{ $record->status }}

            </div>
        </div>
    </div>
</div>
</div>
