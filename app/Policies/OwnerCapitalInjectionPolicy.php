<?php
namespace App\Policies;
use App\Models\OwnerCapitalInjection; use App\Models\User;
class OwnerCapitalInjectionPolicy {
 public function viewAny(User $user): bool { return $user->isOwner() || $user->isManager(); }
 public function view(User $user, OwnerCapitalInjection $injection): bool { return $this->viewAny($user); }
 public function create(User $user): bool { return $user->isOwner(); }
 public function reverse(User $user, OwnerCapitalInjection $injection): bool { return $user->isOwner() && $injection->status === 'posted'; }
}
