<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 1. Component Selection (Main Configuration) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Configuración de Operaciones</h2>
            <form wire:submit="executeOperation">
                {{ $this->form->getComponent('selected_components') }}

                <div class="mt-4 flex flex-wrap gap-4 items-center">
                    {{ $this->form->getComponent('action_type') }}
                    <div class="flex-1 min-w-[300px]">
                        {{ $this->form->getComponent('command_preview') }}
                    </div>
                </div>
            </form>
        </div>

        {{-- 2. File Manager (Folders and Files Selection) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col h-[calc(100vh-25rem)] border border-gray-300 rounded-xl dark:border-none" x-data="{
                draggedItemId: null,
                isDragging: false,
            }">
                {{-- Header with Breadcrumbs and Controls --}}
                <div
                    class="flex items-center justify-between border-b border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-4 rounded-t-xl">
                    {{-- Breadcrumbs --}}
                    <nav class="flex items-center space-x-2 text-sm">
                        @foreach($this->breadcrumbs as $index => $crumb)
                            @if($index > 0)
                                @svg('heroicon-o-chevron-right', 'w-4 h-4 text-gray-400')
                            @endif
                            @if($index === count($this->breadcrumbs) - 1)
                                <span class="font-medium text-gray-900 dark:text-white">{{ $crumb['name'] }}</span>
                            @else
                                <button
                                    x-on:click="$wire.navigateTo({{ json_encode($crumb['id']) }})"
                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                                >
                                    {{ $crumb['name'] }}
                                </button>
                            @endif
                        @endforeach
                    </nav>

                    {{-- Portal Operations Toolbar --}}
                    <div class="flex items-center gap-3">
                        <x-filament::button
                            wire:click="executeOperation"
                            color="primary"
                            icon="heroicon-o-archive-box"
                            size="sm"
                        >
                            Ejecutar Backup/Sync
                        </x-filament::button>

                        <x-filament::button
                            wire:click="previewChanges"
                            color="gray"
                            icon="heroicon-o-eye"
                            size="sm"
                        >
                            Vista Previa
                        </x-filament::button>

                        <div class="h-6 w-px bg-gray-300 dark:bg-gray-700"></div>

                        {{-- Selection info --}}
                        @if(count($this->selected_folders) > 0)
                            <div
                                class="flex items-center gap-2 px-2 py-1 bg-primary-50 dark:bg-primary-900/20 rounded-md border border-primary-200 dark:border-primary-800">
                                <span class="text-xs font-medium text-primary-700 dark:text-primary-400">
                                    {{ count($this->selected_folders) }} carpetas seleccionadas
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- FM Controls --}}
                    <div class="flex items-center gap-2">
                        @if(!$this->isReadOnly())
                            <x-filament::button
                                x-on:click="$dispatch('open-modal', { id: 'create-folder-modal' })"
                                size="sm"
                                variant="outline"
                                icon="heroicon-o-folder-plus"
                                title="Nueva Carpeta"
                            />
                        @endif

                        <x-filament::button
                            wire:click="refresh"
                            size="sm"
                            color="gray"
                            icon="heroicon-o-arrow-path"
                            title="Actualizar"
                        />

                        {{-- View Mode Toggle --}}
                        <div
                            class="flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 p-1">
                            <button
                                wire:click="setViewMode('grid')"
                                class="p-1 rounded {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow-sm' : '' }}"
                            >
                                @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                            </button>
                            <button
                                wire:click="setViewMode('list')"
                                class="p-1 rounded {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow-sm' : '' }}"
                            >
                                @svg('heroicon-o-list-bullet', 'w-4 h-4')
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Main Content Area --}}
                <div class="flex flex-1 overflow-hidden">
                    {{-- Sidebar --}}
                    @if($this->shouldShowPageSidebar())
                        <aside
                            class="w-64 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4 overflow-y-auto">
                            <h2 class="px-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $this->getSidebarHeading() }}</h2>

                            {{-- Root Folder --}}
                            <nav class="space-y-1">
                                <div
                                    x-data="{ showActions: false }"
                                    @mouseenter="showActions = true"
                                    @mouseleave="showActions = false"
                                    class="flex w-full items-center gap-1 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-gray-200 dark:hover:bg-gray-700 {{ $currentPath === null ? 'font-medium' : '' }}"
                                >
                                    {{-- Folder icon and name (clickable to navigate) --}}
                                    <button
                                        wire:click="navigateTo(null)"
                                        class="flex items-center gap-2 flex-1 min-w-0 text-left"
                                    >
                                        @svg('heroicon-o-folder', 'w-4 h-4 text-primary-500 shrink-0')
                                        <span
                                            class="truncate text-gray-700 dark:text-gray-300">{{ $this->getSidebarRootLabel() }}</span>
                                    </button>

                                    {{-- Right side container for badge/actions (fixed width to prevent layout shift) --}}
                                    @php $rootFileCount = $this->rootFileCount; @endphp
                                    <div class="relative shrink-0 flex items-center justify-end"
                                         style="min-width: 72px;">
                                        {{-- File count badge (shown when not hovered) --}}
                                        @if($rootFileCount > 0)
                                            <span
                                                class="absolute right-0 text-xs font-medium font-mono text-primary-600 dark:text-primary-400 transition-opacity duration-100"
                                                :class="showActions ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                                            >
                                            {{ $rootFileCount }}
                                        </span>
                                        @endif

                                        @if(!$this->isReadOnly())
                                            {{-- Hover actions (shown when hovered) --}}
                                            <div
                                                class="flex items-center gap-0.5 transition-opacity duration-100"
                                                :class="showActions ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                            >
                                                {{-- Add folder in root --}}
                                                <button
                                                    x-on:click.stop="$dispatch('open-modal', { id: 'create-folder-modal' })"
                                                    class="p-1 rounded hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                                    title="Add folder"
                                                >
                                                    @svg('heroicon-o-folder-plus', 'w-3.5 h-3.5')
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Folder Tree (children of Root, indented) --}}
                                @include('filemanager::filament.pages.partials.folder-tree', ['folders' => $this->folderTree, 'level' => 1, 'currentPath' => $currentPath, 'isReadOnly' => $this->isReadOnly()])
                            </nav>
                        </aside>
                    @endif

                    {{-- Content Area --}}
                    <main class="flex-1 overflow-y-auto bg-white dark:bg-gray-900 p-6">
                        @if($this->items->isEmpty())
                            {{-- Empty State --}}
                            <div class="flex flex-col items-center justify-center h-full">
                                @svg('heroicon-o-folder-open', 'w-16 h-16 text-gray-400 dark:text-gray-500 mb-4')
                                <p class="text-lg text-gray-600 dark:text-gray-400">This folder is empty</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500">Create a new folder or upload files
                                    to get started</p>
                            </div>
                        @else
                            @if($viewMode === 'grid')
                                {{-- Grid View --}}
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    @foreach($this->items as $item)
                                        @include('filemanager::filament.pages.partials.file-card', [
                                            'item' => $item,
                                            'isReadOnly' => $this->isReadOnly(),
                                            'isFolderSelected' => $item->isFolder() && $this->isFolderSelected($item->getPath())
                                        ])
                                    @endforeach
                                </div>
                            @else
                                {{-- List View --}}
                                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($this->items as $item)
                                        @include('filemanager::filament.pages.partials.file-list-item', ['item' => $item, 'isReadOnly' => $this->isReadOnly()])
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </main>
                </div>
            </div>
        </div>
        {{-- Side-by-side: Selected Folders and System Status --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Selected Folders List --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    @svg('heroicon-o-folder-open', 'w-5 h-5 text-primary-500')
                    Carpetas Seleccionadas en File Manager
                </h2>

                @if(count($this->selected_folders) > 0)
                    <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2">
                        @foreach($this->selected_folders as $folder)
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    @svg('heroicon-o-folder', 'w-5 h-5 text-blue-500 shrink-0')
                                    <div class="flex flex-col min-w-0">
                                                <span
                                                    class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ basename($folder) }}
                                                </span>
                                        <span class="text-xs text-gray-500 truncate">
                                                    {{ str_replace(base_path() . '/', '', $folder) }}
                                                </span>
                                    </div>
                                </div>
                                <button
                                    wire:click="toggleFolderSelection('{{ $folder }}')"
                                    class="text-gray-400 hover:text-danger-500 transition-colors"
                                >
                                    @svg('heroicon-o-x-mark', 'w-5 h-5')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div
                        class="flex flex-col items-center justify-center py-8 text-center bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                        @svg('heroicon-o-cursor-arrow-ripple', 'w-10 h-10 text-gray-400 mb-2')
                        <p class="text-sm text-gray-500">Haz clic en las carpetas del File Manager para
                            seleccionarlas</p>
                    </div>
                @endif
            </div>

            {{-- System Status & Logs --}}
            <div>
                {{ $this->form->getComponent('system_status') }}
            </div>
        </div>
        {{-- Create Folder Modal --}}
        <x-filament::modal id="create-folder-modal" width="md">
            <x-slot name="heading">
                Create New Folder
            </x-slot>

            <x-slot name="description">
                Enter a name for your new folder
            </x-slot>

            <div class="space-y-4">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="newFolderName"
                        placeholder="My Videos"
                        wire:keydown.enter="createFolder"
                        autofocus
                    />
                </x-filament::input.wrapper>
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'create-folder-modal' })"
                    color="gray"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button
                    wire:click="createFolder"
                >
                    Create Folder
                </x-filament::button>
            </x-slot>
        </x-filament::modal>

        {{-- Move Item Modal --}}
        <x-filament::modal id="move-item-modal" width="md">
            <x-slot name="heading">
                @if(count($itemsToMove) > 0)
                    Move {{ count($itemsToMove) }} Item(s)
                @else
                    Move to Folder
                @endif
            </x-slot>

            <x-slot name="description">
                Select a destination folder
            </x-slot>

            <div class="max-h-96 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 p-2">
                {{-- Root option --}}
                <button
                    wire:click="setMoveTarget(null)"
                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors hover:bg-gray-100 dark:hover:bg-gray-800 {{ $moveTargetPath === null ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600' : '' }}"
                >
                    @svg('heroicon-o-folder', 'w-4 h-4')
                    <span>Root</span>
                </button>

                {{-- Folder options --}}
                @foreach($this->allFolders as $folder)
                    @php
                        $folderId = $folder->getIdentifier();
                        $itemBeingMoved = $this->itemToMove;
                        $isCurrentFolder = $itemBeingMoved && $itemBeingMoved->getParentPath() === $folder->getPath();
                        $isSameItem = $itemToMoveId === $folderId;
                        $isBulkMove = count($itemsToMove) > 0;
                        $isDisabled = $isBulkMove ? in_array($folderId, $itemsToMove) : ($isCurrentFolder || $isSameItem);
                    @endphp
                    <button
                        x-on:click="$wire.setMoveTarget({{ json_encode($folderId) }})"
                        @if($isDisabled) disabled @endif
                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors
                        {{ $moveTargetPath === $folderId ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600' : 'hover:bg-gray-100 dark:hover:bg-gray-800' }}
                        {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                        style="padding-left: {{ $folder->getDepth() * 16 + 12 }}px"
                    >
                        @svg('heroicon-o-folder', 'w-4 h-4')
                        <span>{{ $folder->getName() }}</span>
                    </button>
                @endforeach
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'move-item-modal' })"
                    color="gray"
                >
                    Cancel
                </x-filament::button>
                @if(count($itemsToMove) > 0)
                    <x-filament::button
                        wire:click="moveSelected"
                    >
                        Move {{ count($itemsToMove) }} Item(s)
                    </x-filament::button>
                @else
                    <x-filament::button
                        wire:click="moveItem"
                    >
                        Move Here
                    </x-filament::button>
                @endif
            </x-slot>
        </x-filament::modal>

        {{-- Create Subfolder Modal --}}
        <x-filament::modal id="create-subfolder-modal" width="md">
            <x-slot name="heading">
                Create Subfolder
            </x-slot>

            <x-slot name="description">
                @if($this->subfolderParent)
                    Create a new folder inside "{{ $this->subfolderParent->getName() }}"
                @else
                    Enter a name for your new subfolder
                @endif
            </x-slot>

            <div class="space-y-4">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="subfolderName"
                        placeholder="New Folder"
                        wire:keydown.enter="createSubfolder"
                        autofocus
                    />
                </x-filament::input.wrapper>
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'create-subfolder-modal' })"
                    color="gray"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button
                    wire:click="createSubfolder"
                >
                    Create Subfolder
                </x-filament::button>
            </x-slot>
        </x-filament::modal>

        {{-- Rename Item Modal --}}
        <x-filament::modal id="rename-item-modal" width="md">
            <x-slot name="heading">
                Rename Item
            </x-slot>

            <x-slot name="description">
                @if($this->itemToRename)
                    Rename "{{ $this->itemToRename->getName() }}"
                @else
                    Enter a new name for this item
                @endif
            </x-slot>

            <div class="space-y-4">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="renameItemName"
                        placeholder="New name"
                        wire:keydown.enter="renameItem"
                        autofocus
                    />
                </x-filament::input.wrapper>
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'rename-item-modal' })"
                    color="gray"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button
                    wire:click="renameItem"
                >
                    Rename
                </x-filament::button>
            </x-slot>
        </x-filament::modal>

        {{-- Upload Files Modal --}}
        <x-filament::modal id="upload-files-modal" width="lg">
            <x-slot name="heading">
                Upload Files
            </x-slot>

            <x-slot name="description">
                @php
                    $maxSizeMB = round(config('filemanager.upload.max_file_size', 102400) / 1024, 0);
                @endphp
                Select one or more files to upload (max {{ $maxSizeMB }}MB per file)
            </x-slot>

            <div class="space-y-4">
                <div
                    x-data="{ isDragging: false }"
                    x-on:dragover.prevent="isDragging = true"
                    x-on:dragleave.prevent="isDragging = false"
                    x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    class="relative border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                    :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-300 dark:border-gray-600'"
                >
                    <input
                        type="file"
                        x-ref="fileInput"
                        wire:model.live="uploadedFiles"
                        multiple
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />

                    <div class="flex flex-col items-center">
                        @svg('heroicon-o-cloud-arrow-up', 'w-10 h-10 text-gray-400 mb-2')
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium text-primary-600">Click to upload</span> or drag and drop
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Any file type up to {{ $maxSizeMB }}MB</p>
                    </div>
                </div>

                {{-- Selected files preview --}}
                @if(count($uploadedFiles) > 0)
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ count($uploadedFiles) }} file(s) ready to upload:
                        </p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 max-h-32 overflow-y-auto">
                            @foreach($uploadedFiles as $file)
                                <li class="flex items-center gap-2">
                                    @svg('heroicon-o-check-circle', 'w-4 h-4 shrink-0 text-success-500')
                                    <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                    <span
                                        class="text-xs text-gray-400">({{ number_format($file->getSize() / 1024, 1) }} KB)</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    x-on:click="$dispatch('close-modal', { id: 'upload-files-modal' })"
                    wire:click="clearUploadedFiles"
                    color="gray"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button
                    wire:click="uploadFiles"
                    wire:loading.attr="disabled"
                    wire:target="uploadedFiles, uploadFiles"
                    :disabled="count($uploadedFiles) === 0"
                >
                <span wire:loading.remove wire:target="uploadedFiles, uploadFiles">
                    @if(count($uploadedFiles) > 0)
                        Upload {{ count($uploadedFiles) }} File(s)
                    @else
                        Select Files First
                    @endif
                </span>
                    <span wire:loading wire:target="uploadedFiles">Processing...</span>
                    <span wire:loading wire:target="uploadFiles">Uploading...</span>
                </x-filament::button>
            </x-slot>
        </x-filament::modal>

        {{-- Preview Modal --}}
        <x-filament::modal id="preview-modal" width="5xl" :close-by-clicking-away="true">
            @if($this->previewItem)
                @php
                    $previewItem = $this->previewItem;
                    $fileType = $this->previewFileType;
                    $previewUrl = $this->getPreviewUrl();
                    $textContent = $this->getTextContent();
                    $viewerComponent = $fileType?->viewerComponent();
                @endphp

                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        @if($fileType)
                            <x-dynamic-component
                                :component="$fileType->icon()"
                                class="w-6 h-6 {{ $fileType->iconColor() }}"
                            />
                        @else
                            @svg('heroicon-o-document', 'w-6 h-6 text-gray-400')
                        @endif
                        <span class="truncate">{{ $previewItem->getName() }}</span>
                    </div>
                </x-slot>

                <x-slot name="description">
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>{{ $previewItem->getPath() }}</span>
                        @if($fileType)
                            <span
                                class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs">{{ $fileType->label() }}</span>
                        @endif
                        @if($previewItem->getSize())
                            <span>{{ $previewItem->getFormattedSize() }}</span>
                        @endif
                        @if($previewItem->getDuration())
                            <span>{{ $previewItem->getFormattedDuration() }}</span>
                        @endif
                    </div>
                </x-slot>

                <div class="min-h-[300px] max-h-[70vh] overflow-auto">
                    @if($viewerComponent && $previewUrl)
                        {{-- Use dynamic viewer component from FileType --}}
                        @if($fileType->identifier() === 'text' && $textContent !== null)
                            @include($viewerComponent, ['content' => $textContent, 'url' => $previewUrl, 'item' => $previewItem])
                        @else
                            @include($viewerComponent, ['url' => $previewUrl, 'item' => $previewItem, 'fileType' => $fileType])
                        @endif
                    @elseif($fileType && !$fileType->canPreview())
                        {{-- No Preview Available - use fallback --}}
                        @include('filemanager::components.viewers.fallback', [
                            'url' => $previewUrl,
                            'item' => $previewItem,
                            'fileType' => $fileType
                        ])
                    @else
                        {{-- Fallback for unknown types --}}
                        @include('filemanager::components.viewers.fallback', [
                            'url' => $previewUrl,
                            'item' => $previewItem,
                            'fileType' => $fileType
                        ])
                    @endif
                </div>

                <x-slot name="footerActions">
                    <div class="flex w-full justify-between">
                        <div class="flex gap-2">
                            @if($previewUrl)
                                <x-filament::button
                                    tag="a"
                                    href="{{ $previewUrl }}"
                                    target="_blank"
                                    color="gray"
                                    icon="heroicon-o-download"
                                >
                                    Download
                                </x-filament::button>
                            @endif
                            @if(!$this->isReadOnly())
                                <x-filament::button
                                    x-on:click="$wire.openMoveDialog({{ json_encode($previewItem->getIdentifier()) }})"
                                    color="gray"
                                    icon="heroicon-o-arrow-right-circle"
                                >
                                    Move
                                </x-filament::button>
                                <x-filament::button
                                    x-on:click="$wire.openRenameDialog({{ json_encode($previewItem->getIdentifier()) }})"
                                    color="gray"
                                    icon="heroicon-o-pencil"
                                >
                                    Rename
                                </x-filament::button>
                            @endif
                        </div>
                        <x-filament::button
                            x-on:click="$dispatch('close-modal', { id: 'preview-modal' })"
                        >
                            Close
                        </x-filament::button>
                    </div>
                </x-slot>
            @endif
        </x-filament::modal>
    </div>
</x-filament-panels::page>
