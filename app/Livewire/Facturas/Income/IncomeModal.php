<?php

namespace App\Livewire\Facturas\Income;

use App\Models\Income;
use Developermithu\Tallcraftui\Traits\WithTcToast;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class IncomeModal extends Component
{
    use WithTcToast;

    public $incomeId = null;
    public $isView = false;

    public string $icon = '';

    public $source = null, $amount = null, $income_date = null, $note = null;

    public function rules(): array
    {
        return [
            'source' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'note' => 'nullable|string',
            'icon' => 'nullable|string|max:64',
        ];
    }

    public function messages(): array
    {
        return [
            'source.required' => 'The income source field is required.',
            'amount.required' => 'The amount field is required.',
            'income_date.required' => 'The date field is required.',
        ];
    }


    public function updated($propertyName)
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->validateOnly($propertyName);
    }

    // Submit the form data
    public function submit()
    {

        // dd($this->iconStyle);
        $this->validate();

        // Prepare the payload
        $saveData = [
            'user_id'    => Auth::user()->id,
            'source'       => $this->source,
            'amount'       => $this->amount,
            'income_date'       => $this->income_date,
            'note'       => $this->note,
            'icon'       => $this->icon,
        ];

        if ($this->incomeId) {
            $income = Income::find($this->incomeId);

            if (!$income) {
                $this->error(
                    title: 'Income not found!',
                    position: 'top-right',
                    showProgress: true,
                    showCloseIcon: true,
                );
                return;
            }

            // Check for changes WITHOUT persisting
            $original = $income->getAttributes();
            $income->fill($saveData);
            $hasChanges = $income->isDirty();
            $income->fill($original); // revert to original before actual update

            if (!$hasChanges) {
                $this->warning(
                    title: 'Nothing to update!',
                    position: 'top-right',
                    showProgress: true,
                    showCloseIcon: true,
                );
                $this->dispatch('incomes:refresh');
                Flux::modal('income-modal')->close();
                return;
            }

            // Persist updates
            $income->update($saveData);

            $this->success(
                title: 'income updated successfully.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );
        } else {
            Income::create($saveData);

            // Reset fields you want cleared for a fresh form (adjust as needed)
            $this->reset();

            $this->success(
                title: 'income created successfully.',
                position: 'top-right',
                showProgress: true,
                showCloseIcon: true,
            );
        }

        $this->dispatch('incomes:refresh');
        Flux::modal('income-modal')->close();
    }


    #[On('open-income-modal')]
    public function incomeDetail($mode, $income = null)
    {

        $this->resetErrorBag();
        $this->resetValidation();

        $this->isView = $mode === 'view';

        if ($mode === 'create') {

            $this->isView = false;
            $this->reset();
        } else {
            // dd($income);
            $this->incomeId = $income['id'];

            $this->source = $income['source'];
            $this->amount = $income['amount'];
            $this->income_date = $income['income_date'];
            $this->note = $income['note'];
            $this->icon = $income['icon'];
        }
    }

    public function render()
    {

        return view('livewire.income.income-modal');
    }
}
