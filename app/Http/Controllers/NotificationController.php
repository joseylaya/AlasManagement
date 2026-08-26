<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = Notification::visibleTo($user)
            ->with(['announcement', 'reads' => fn ($query) => $query->where('user_id', $user->id)])
            ->latest()
            ->take(10)
            ->get()
            ->each(fn (Notification $notification) => $notification->applyReadStateFor($user));

        return response()->json([
            'unread_count' => $notifications->where('is_read', false)->count(),
            'notifications' => $notifications->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'is_read' => $notification->is_read,
                'is_announcement' => str_starts_with($notification->type, 'announcement.'),
                'image_url' => $notification->announcement?->image_path
                    ? asset('storage/'.$notification->announcement->image_path)
                    : null,
                'created_at' => $notification->created_at->diffForHumans(),
                'open_url' => route('notifications.open', $notification),
            ])->values(),
        ]);
    }

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
