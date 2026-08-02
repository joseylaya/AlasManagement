<?php

namespace App\Livewire\Auth;

use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;

    protected array $rules = [
        'username' => 'required|string',
        'password' => 'required',
    ];

    public function login(): void
    {
        $this->validate();

        if (Auth::attempt(['username' => Str::lower(trim($this->username)), 'password' => $this->password, 'status' => 'active'], $this->remember)) {
            session()->regenerate();
            
            ActivityLogService::log(
                'User Logged In',
                "User " . Auth::user()->name . " (" . Auth::user()->role . ") logged in.",
                Auth::user()
            );

            // Login changes the whole application shell (guest → authenticated).
            // Use a normal redirect here so Alpine and the authenticated layout
            // initialize from a clean document. SPA navigation is kept for links
            // within the authenticated workspace.
            $this->redirectIntended(route('dashboard'));
            return;
        }

        $this->addError('username', 'The provided credentials do not match our records or account is inactive.');
    }

    public function demoLogin(string $role): void
    {
        $username = match ($role) {
            'owner' => 'owner',
            'manager' => 'manager',
            'staff' => 'staff',
            default => 'owner',
        };

        $this->username = $username;
        $this->password = 'password';

        $this->login();
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest', ['title' => 'Login — ALAS Business Manager']);
    }
}
