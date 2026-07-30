<?php

namespace App\Livewire\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedUser = '';

    public function render()
    {
        $query = ActivityLog::with('user');
        $isStaff = auth()->user()->isStaff();

        if ($isStaff) {
            $query->where('user_id', auth()->id());
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('action', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if (!$isStaff && !empty($this->selectedUser)) {
            $query->where('user_id', $this->selectedUser);
        }

        $logs = $query->latest('id')->paginate(15);
        $users = $isStaff
            ? collect()
            : User::all();

        return view('livewire.activity-logs.index', [
            'logs' => $logs,
            'users' => $users,
        ])->layout('layouts.app', ['pageHeader' => 'Activity Audit Logs']);
    }
}
