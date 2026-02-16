<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChangeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ChangeNotification::with(['designChange.component', 'component'])
            ->orderByRaw("FIELD(status, 'unread', 'read', 'actioned')")
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('team')) {
            $query->where('team', $request->team);
        }

        $notifications = $query->paginate($request->get('per_page', 50));

        return response()->json($notifications);
    }

    public function markRead(ChangeNotification $notification): JsonResponse
    {
        $notification->update([
            'status'  => 'read',
            'read_at' => now(),
        ]);

        return response()->json($notification);
    }

    public function markActioned(ChangeNotification $notification): JsonResponse
    {
        $notification->update([
            'status'  => 'actioned',
            'read_at' => $notification->read_at ?? now(),
        ]);

        return response()->json($notification);
    }

    public function unreadCount(): JsonResponse
    {
        $count = ChangeNotification::where('status', 'unread')->count();

        return response()->json(['unread_count' => $count]);
    }
}