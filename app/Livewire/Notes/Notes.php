<?php

namespace App\Livewire\Notes;

use App\Models\Note;
use App\Models\Folder;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class Notes extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $folderFilter = null;
    public ?int $projectFilter = null;
    public ?string $tagFilter = null;

    public bool $showEditor = false;
    public bool $showFolderModal = false;
    public bool $showFileManager = false;
    public ?int $editingId = null;

    public array $form = [
        'title' => '',
        'content' => '',
        'folder_id' => null,
        'project_id' => null,
        'tags' => [],
    ];

    public array $folderForm = [
        'name' => '',
        'description' => '',
        'color' => '#6366f1',
        'icon' => null,
        'status' => 'active',
        'project_id' => null,
        'parent_id' => null,
        'sort_order' => 0,
    ];

    public $notes;
    public $folders;
    public $projects;
    public $pinnedNotes;
    public $recentNotes;

    protected $rules = [
        'form.title' => 'required|string|max:255',
        'form.content' => 'required|string',
        'form.folder_id' => 'nullable|exists:folders,id',
        'form.project_id' => 'nullable|exists:projects,id',
        'form.tags' => 'array',
    ];

    protected $folderRules = [
        'folderForm.name' => 'required|string|max:255',
        'folderForm.color' => 'required|string',
        'folderForm.status' => 'required|in:active,archived',
    ];

    public function mount(): void
    {
        $this->init();
    }

    public function init(): void
    {
        $this->notes = $this->getNotes();
        $this->folders = Folder::with('project', 'parent')->orderBy('sort_order')->get();
        $this->projects = Project::active()->orderBy('sort_order')->get();
        $this->pinnedNotes = Note::pinned()->with('folder', 'project')->latest('pinned_at')->get();
        $this->recentNotes = Note::recent()->with('folder', 'project')->limit(10)->get();
    }

    public function getNotes()
    {
        return Note::query()
            ->with('folder', 'project', 'createdBy')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('content', 'like', "%{$this->search}%")
            )
            ->when($this->folderFilter, fn ($q) => $q->where('folder_id', $this->folderFilter))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->tagFilter, fn ($q) => $q->whereJsonContains('tags', $this->tagFilter))
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->get();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->folderFilter = null;
        $this->projectFilter = null;
        $this->tagFilter = null;
        $this->notes = $this->getNotes();
    }

    public function newNote(): void
    {
        $this->reset(['editingId']);
        $this->form = [
            'title' => '',
            'content' => '',
            'folder_id' => $this->folderFilter,
            'project_id' => $this->projectFilter,
            'tags' => [],
        ];
        $this->showEditor = true;
    }

    public function editNote(int $id): void
    {
        $note = Note::findOrFail($id);
        $this->editingId = $note->id;

        $this->form['title'] = $note->title;
        $this->form['content'] = $note->content;
        $this->form['folder_id'] = $note->folder_id;
        $this->form['project_id'] = $note->project_id;
        $this->form['tags'] = $note->tags ?? [];

        $this->showEditor = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $note = Note::findOrFail($this->editingId);
            $note->update($this->form);

            // Si la nota está sincronizada con ClickUp, actualizarla también allí
            if ($note->clickup_doc_id) {
                try {
                    $this->updateNoteInClickUp($note);
                } catch (\Exception $e) {
                    // No fallar el guardado local si falla la actualización en ClickUp
                    $this->dispatch('toast', type: 'warning', title: 'Nota guardada localmente, pero error al actualizar en ClickUp: ' . $e->getMessage());
                }
            }
        } else {
            $note = Note::create(array_merge($this->form, [
                'created_by' => auth()->id(),
            ]));
        }

        $this->showEditor = false;
        $this->editingId = null;
        $this->notes = $this->getNotes();
        $this->pinnedNotes = Note::pinned()->with('folder', 'project')->latest('pinned_at')->get();
        $this->recentNotes = Note::recent()->with('folder', 'project')->limit(10)->get();

        $this->dispatch('toast', type: 'success', title: $this->editingId ? 'Nota actualizada' : 'Nota creada');
    }

    public function deleteNote(int $id): void
    {
        Note::findOrFail($id)->delete();
        $this->notes = $this->getNotes();
        $this->pinnedNotes = Note::pinned()->with('folder', 'project')->latest('pinned_at')->get();
        $this->recentNotes = Note::recent()->with('folder', 'project')->limit(10)->get();
        $this->dispatch('toast', type: 'success', title: 'Nota eliminada');
    }

    public function togglePin(int $id): void
    {
        $note = Note::findOrFail($id);
        if ($note->is_pinned) {
            $note->unpin();
        } else {
            $note->pin();
        }
        $this->notes = $this->getNotes();
        $this->pinnedNotes = Note::pinned()->with('folder', 'project')->latest('pinned_at')->get();
    }

    public function newFolder(): void
    {
        $this->reset(['folderForm']);
        $this->folderForm['color'] = '#6366f1';
        $this->folderForm['status'] = 'active';
        $this->folderForm['project_id'] = $this->projectFilter;
        $this->showFolderModal = true;
    }

    public function saveFolder(): void
    {
        $this->validate($this->folderRules);

        Folder::create(array_merge($this->folderForm, [
            'created_by' => auth()->id(),
        ]));
        $this->showFolderModal = false;
        $this->folders = Folder::with('project', 'parent')->orderBy('sort_order')->get();
        $this->dispatch('toast', type: 'success', title: 'Carpeta creada');
    }

    public function toggleFileManager(): void
    {
        $this->showFileManager = !$this->showFileManager;
    }

    public function exportToClickUp(int $id): void
    {
        $note = Note::findOrFail($id);

        try {
            $clickupDocId = $this->syncNoteToClickUp($note);

            $note->update(['clickup_doc_id' => $clickupDocId]);
            $this->notes = $this->getNotes();

            $this->dispatch('toast', type: 'success', title: 'Nota exportada a ClickUp');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', title: 'Error al exportar: ' . $e->getMessage());
        }
    }

    public function importFromClickUp(): void
    {
        try {
            $importedCount = $this->syncNotesFromClickUp();
            $this->notes = $this->getNotes();

            $this->dispatch('toast', type: 'success', title: "{$importedCount} notas importadas de ClickUp");
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', title: 'Error al importar: ' . $e->getMessage());
        }
    }

    private function syncNoteToClickUp(Note $note): string
    {
        $clickupApiToken = config('services.clickup.api_token');
        $clickupWorkspaceId = config('services.clickup.workspace_id');
        $clickupSpaceId = config('services.clickup.space_id');

        if (!$clickupApiToken || !$clickupWorkspaceId) {
            throw new \Exception('Configuración de ClickUp no encontrada');
        }

        $client = new \GuzzleHttp\Client();

        // Usar API v3 para crear docs
        $response = $client->post("https://api.clickup.com/api/v3/workspaces/{$clickupWorkspaceId}/docs", [
            'headers' => [
                'Authorization' => $clickupApiToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'name' => $note->title,
                'parent' => [
                    'id' => $clickupSpaceId ?? $clickupWorkspaceId,
                    'type' => $clickupSpaceId ? 4 : 12, // 4 = Space, 12 = Workspace
                ],
                'visibility' => 'PRIVATE',
                'create_page' => true,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['id'];
    }

    private function syncNotesFromClickUp(): int
    {
        $clickupApiToken = config('services.clickup.api_token');
        $clickupWorkspaceId = config('services.clickup.workspace_id');

        if (!$clickupApiToken || !$clickupWorkspaceId) {
            throw new \Exception('Configuración de ClickUp no encontrada');
        }

        $client = new \GuzzleHttp\Client();

        // Usar API v3 para obtener docs
        $response = $client->get("https://api.clickup.com/api/v3/workspaces/{$clickupWorkspaceId}/docs", [
            'headers' => [
                'Authorization' => $clickupApiToken,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $importedCount = 0;

        if (isset($data['docs'])) {
            foreach ($data['docs'] as $clickupDoc) {
                $existingNote = Note::where('clickup_doc_id', $clickupDoc['id'])->first();

                if (!$existingNote) {
                    // Obtener el contenido completo del doc mediante sus páginas
                    $content = $this->getDocContent($clickupDoc['id'], $clickupWorkspaceId, $clickupApiToken);

                    Note::create([
                        'title' => $clickupDoc['name'] ?? 'Sin título',
                        'content' => $content,
                        'clickup_doc_id' => $clickupDoc['id'],
                        'created_by' => auth()->id(),
                    ]);
                    $importedCount++;
                }
            }
        }

        return $importedCount;
    }

    private function updateNoteInClickUp(Note $note): void
    {
        $clickupApiToken = config('services.clickup.api_token');
        $clickupWorkspaceId = config('services.clickup.workspace_id');

        if (!$clickupApiToken || !$clickupWorkspaceId || !$note->clickup_doc_id) {
            throw new \Exception('Configuración de ClickUp no encontrada o nota no sincronizada');
        }

        $client = new \GuzzleHttp\Client();

        try {
            // Obtener las páginas del doc
            $pagesResponse = $client->get("https://api.clickup.com/api/v3/workspaces/{$clickupWorkspaceId}/docs/{$note->clickup_doc_id}/pages", [
                'headers' => [
                    'Authorization' => $clickupApiToken,
                ],
            ]);

            $pagesData = json_decode($pagesResponse->getBody(), true);

            if (isset($pagesData['pages']) && !empty($pagesData['pages'])) {
                // Actualizar la primera página del doc
                $firstPage = $pagesData['pages'][0];

                $client->put("https://api.clickup.com/api/v3/workspaces/{$clickupWorkspaceId}/docs/{$note->clickup_doc_id}/pages/{$firstPage['id']}", [
                    'headers' => [
                        'Authorization' => $clickupApiToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'name' => $note->title,
                        'content' => $note->content,
                        'content_edit_mode' => 'replace',
                        'content_format' => 'text/md',
                    ],
                ]);
            } else {
                // Si no hay páginas, crear una nueva
                $client->post("https://api.clickup.com/api/v3/workspaces/{$clickupWorkspaceId}/docs/{$note->clickup_doc_id}/pages", [
                    'headers' => [
                        'Authorization' => $clickupApiToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'name' => $note->title,
                        'content' => $note->content,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar nota en ClickUp: ' . $e->getMessage());
        }
    }

    private function getDocContent(string $docId, string $workspaceId, string $apiToken): string
    {
        $client = new \GuzzleHttp\Client();
        $content = '';

        try {
            // Obtener las páginas del doc
            $pagesResponse = $client->get("https://api.clickup.com/api/v3/workspaces/{$workspaceId}/docs/{$docId}/pages", [
                'headers' => [
                    'Authorization' => $apiToken,
                ],
            ]);

            $pagesData = json_decode($pagesResponse->getBody(), true);

            if (isset($pagesData['pages'])) {
                foreach ($pagesData['pages'] as $page) {
                    // Obtener el contenido de cada página
                    $pageResponse = $client->get("https://api.clickup.com/api/v3/workspaces/{$workspaceId}/docs/{$docId}/pages/{$page['id']}", [
                        'headers' => [
                            'Authorization' => $apiToken,
                        ],
                    ]);

                    $pageData = json_decode($pageResponse->getBody(), true);

                    if (isset($pageData['content'])) {
                        $content .= $pageData['content'] . "\n\n";
                    }
                }
            }
        } catch (\Exception $e) {
            // Si falla obtener el contenido detallado, intentar con el contenido básico
            try {
                $docResponse = $client->get("https://api.clickup.com/api/v3/workspaces/{$workspaceId}/docs/{$docId}", [
                    'headers' => [
                        'Authorization' => $apiToken,
                    ],
                ]);

                $docData = json_decode($docResponse->getBody(), true);
                $content = $docData['content'] ?? '';
            } catch (\Exception $e2) {
                $content = '';
            }
        }

        return trim($content);
    }

    public function render()
    {
        return view('livewire.notes.notes')
            ->layout('layouts.front');
    }
}
