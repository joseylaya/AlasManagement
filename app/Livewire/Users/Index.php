<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\ActivityLogService;
use Exception;
use Illuminate\Support\Facades\Hash;

use Livewire\Component;

class Index extends Component
{
    public bool $showUserModal = false;
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = 'password';
    public string $role = 'staff';

    protected array $rules = [
        'name' => 'required|string|max:255',
        'username' => 'required|alpha_dash|max:50|unique:users,username',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
        'role' => 'required|in:owner,manager,staff',
    ];

    public function createUser(): void
    {
        $this->validate();

        try {
            $user = User::create([
                'name' => $this->name,
                'username' => strtolower($this->username),
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'status' => 'active',
            ]);

            ActivityLogService::log(
                'User Account Created',
                "Created employee account for {$user->name} ({$user->email}) with role {$user->role}.",
                $user
            );

            session()->flash('success', "Account for {$user->name} created successfully!");
            $this->showUserModal = false;
            $this->name = '';
            $this->username = '';
            $this->email = '';
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleUserStatus(int $userId): void
    {
        $user = User::findOrFail($userId);
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        ActivityLogService::log(
            'User Status Updated',
            "Updated account status of {$user->name} to {$newStatus}.",
            $user
        );

        session()->flash('success', "Status of {$user->name} updated to {$newStatus}.");
    }

    public function render()
    {
        $users = User::latest('id')->get();

        return view('livewire.users.index', [
            'users' => $users,
        ])->layout('layouts.app', ['pageHeader' => 'Employee Accounts & Permissions']);
    }
}
