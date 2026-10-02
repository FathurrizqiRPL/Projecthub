<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;

class UserTable extends Component
{
    public string $search = '';
    public string $role = '';
    public string $status = '';

    public function render()
    {
        $users = User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where(
                            'name',
                            'like',
                            '%'.$this->search.'%'
                        )
                        ->orWhere(
                            'username',
                            'like',
                            '%'.$this->search.'%'
                        )
                        ->orWhere(
                            'email',
                            'like',
                            '%'.$this->search.'%'
                        );
                });
            })
            ->when($this->role, function ($query) {
                $query->where('role', $this->role);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.users.user-table', [
            'users' => $users,
        ]);
    }
}
