<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): mixed
    {
        $user = $request->user();

        return $this->ok([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->take(10)->get()->map(fn ($n) => array_merge(
                ['id' => $n->id, 'read' => $n->read_at !== null, 'at' => $n->created_at?->diffForHumans()],
                is_array($n->data) ? $n->data : [],
            )),
        ]);
    }

    /** Full, paginated notifications history (the bell only shows the latest 10). */
    public function all(Request $request): mixed
    {
        if ($request->expectsJson() || $request->ajax()) {
            $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

            $paginator = $request->user()->notifications()->latest()->paginate($perPage)->withQueryString();
            $paginator->through(fn ($n) => array_merge(
                ['id' => $n->id, 'read' => $n->read_at !== null, 'at' => $n->created_at?->diffForHumans()],
                is_array($n->data) ? $n->data : [],
            ));

            return $this->paginate($paginator);
        }

        return view('notifications.index');
    }

    public function markRead(Request $request): mixed
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->ok(null, 'Marked as read.');
    }
}
