<?php

namespace App\Livewire\Comunigest;

use App\Models\Employee;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserCrud extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $search = '';

    public string $roleFilter = '';

    public string $activeFilter = '';

    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'employee';

    public bool $active = true;

    public string $phone = '';

    public ?int $employeeId = null;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'active', 'phone', 'employeeId']);
        $this->userId = null;
        $this->active = true;
        $this->role = 'employee';
        $this->employeeId = null;
    }

    public function openNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->active = $user->active;
        $this->phone = $user->phone ?? '';
        $this->employeeId = $user->employee_id;
        $this->password = '';
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'roleFilter', 'activeFilter');
        $this->resetPage();
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$this->userId,
            'role' => 'required|in:admin,employee',
            'active' => 'boolean',
            'phone' => 'nullable|string',
            'employeeId' => 'nullable|exists:employees,id',
        ];

        if ($this->userId === null) {
            $rules['password'] = 'required|string|min:6';
        }

        $validated = $this->validate($rules);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'active' => $validated['active'],
            'phone' => $validated['phone'] ?: null,
            'employee_id' => $validated['employeeId'] ?: null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        if ($this->userId) {
            User::findOrFail($this->userId)->update($data);
        } else {
            User::create($data);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
        $this->closeForm();
    }

    public function render()
    {
        $query = User::with('employee');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        if ($this->activeFilter !== '') {
            $query->where('active', $this->activeFilter === '1');
        }

        return view('livewire.comunigest.user-crud', [
            'users' => $query->orderBy('name')->paginate(10),
            'employees' => Employee::orderBy('name')->pluck('name', 'id')->toArray(),
        ])->layout('layouts.front');
    }
}
