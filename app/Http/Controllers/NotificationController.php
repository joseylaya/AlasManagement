<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function open(Notification $notification): RedirectResponse
    {
        $notification = Notification::visibleTo(auth()->user())->findOrFail($notification->id);

        $notification->markReadBy(auth()->user());

        return redirect($notification->link ?: route('dashboard'));
    }

    public function read(Notification $notification): Response
    {
        $notification = Notification::visibleTo(auth()->user())->findOrFail($notification->id);

        $notification->markReadBy(auth()->user());

        return response()->noContent();
    }
}
