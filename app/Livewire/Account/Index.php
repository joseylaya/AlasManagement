<?php

namespace App\Livewire\Account;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Index extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $successMessage = null;

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->successMessage = null;
            $this->addError('current_password', 'Your current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->password)]);

        ActivityLogService::log(
            'Password Changed',
            "{$user->name} changed their account password.",
            $user,
            null,
            $user,
        );

        NotificationService::send(
            $user,
            'account.password_changed',
            'Password updated',
            'Your account password was changed successfully.',
            route('account.index'),
        );

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->successMessage = 'Password updated successfully.';
        session()->flash('success', 'Your password has been updated.');
    }

    public function render()
    {
        return view('livewire.account.index')
            ->layout('layouts.app', ['pageHeader' => 'My Account']);
    }
}
