<?php

namespace App\Livewire\Facturas\Expense;

use App\Models\Expense;
use Carbon\Carbon;
use Developermithu\Tallcraftui\Traits\WithTcToast;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseIndex extends Component
{

    use WithPagination, WithTcToast;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $perPage = 10;

    #[Url(history: true)]
    public $range = '';

    #[Url(history: true)]
    public $sortBy = 'created_at';

    #[Url(history: true)]
    public $sortDir = 'DESC';

    #[On('expenses:refresh')]
    #[On('expenses:deleted')]
    public function refreshList()
    {
        $this->resetPage();
    }

    // Reset pagination when any of these change
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function updatedPerPage()
    {
        $this->resetPage();
    }
    public function updatedRange()
    {
        $this->resetPage();
    }

    public function setSortBy($sortByField)
    {

        if ($this->sortBy === $sortByField) {
            $this->sortDir = ($this->sortDir == "ASC") ? "DESC" : "ASC";
            return;
        }
        $this->sortBy = $sortByField;
        $this->sortDir = 'DESC';
    }

    public function render()
    {
        $expenses = Expense::search($this->search)
            ->when($this->range, function ($q) {
                [$from, $to] = $this->dateBounds($this->range);
                if ($from && $to) {
                    $q->whereBetween('created_at', [$from, $to]);
                }
            })
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.expense.expense-index', compact('expenses'));
    }

    /**
     * Map filter key -> [from, to] Carbon ranges (inclusive day window).
     */
    private function dateBounds(string $key): array
    {
        $today = Carbon::today();
        $now   = Carbon::now();

        return match ($key) {
            'yesterday'     => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'last_week'     => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'last_month'    => [Carbon::now()->subMonthNoOverflow()->startOfMonth(), Carbon::now()->subMonthNoOverflow()->endOfMonth()],
            'last_7_days'   => [$today->copy()->subDays(7)->startOfDay(), $now],
            'last_30_days'  => [$today->copy()->subDays(30)->startOfDay(), $now],
            default         => [null, null],
        };
    }

    #[On('delete-expense')]
    public function deleteExpense($id)
    {
        $expense = Expense::find($id);

        if ($expense) {

            // Delete all associated files from disk
            $files = $expense->files ?? [];
            if (!empty($files) && is_array($files)) {
                foreach ($files as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }

            $expense->delete();
            $this->dispatch('expenses:deleted');
            $this->success(
                title: 'Expense deleted successfully.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );

            Flux::modal('delete-confirmation-modal')->close();
        } else {
            $this->error(
                title: 'Expense not found.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );
        }
    }
}
