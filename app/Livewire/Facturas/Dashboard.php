<?php

namespace App\Livewire\Facturas;

use App\Models\Project;
use App\Models\Task;
use App\Models\Note;
use App\Models\Factura;
use Livewire\Component;

class Dashboard extends Component
{
    public function getProjectsStats()
    {
        return [
            'total' => Project::count(),
            'active' => Project::where('status', 'active')->count(),
            'completed' => Project::where('phase', 'completed')->count(),
            'inDevelopment' => Project::where('phase', 'development')->count(),
        ];
    }

    public function getTasksStats()
    {
        return [
            'total' => Task::count(),
            'pending' => Task::where('status', 'pending')->count(),
            'inProgress' => Task::where('status', 'in_progress')->count(),
            'completed' => Task::where('status', 'completed')->count(),
            'highPriority' => Task::where('priority', 'high')->count(),
        ];
    }

    public function getNotesStats()
    {
        return [
            'total' => Note::count(),
            'pinned' => Note::where('is_pinned', true)->count(),
            'thisWeek' => Note::where('created_at', '>=', now()->startOfWeek())->count(),
            'withTags' => Note::whereNotNull('tags')->where('tags', '!=', '[]')->count(),
        ];
    }

    public function getInvoicesStats()
    {
        return [
            'total' => Factura::count(),
            'thisMonth' => Factura::whereMonth('fechaemitido', now()->month)
                ->whereYear('fechaemitido', now()->year)
                ->count(),
            'paid' => Factura::where('pagada', true)->count(),
            'unpaid' => Factura::where('pagada', false)->count(),
            'totalAmount' => Factura::sum('importe'),
        ];
    }

    public function getRecentProjects()
    {
        return Project::latest()->take(5)->get();
    }

    public function getPendingTasks()
    {
        return Task::where('status', 'pending')
            ->orWhere('status', 'in_progress')
            ->latest()
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.facturas.dashboard')
            ->layout('layouts.front')
            ->with([
                'projectsStats' => $this->getProjectsStats(),
                'tasksStats' => $this->getTasksStats(),
                'notesStats' => $this->getNotesStats(),
                'invoicesStats' => $this->getInvoicesStats(),
                'recentProjects' => $this->getRecentProjects(),
                'pendingTasks' => $this->getPendingTasks(),
            ]);
    }
}
