<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationControllerMobile extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($perPage);

        $notifications->getCollection()->transform(
            fn (DatabaseNotification $notification): array => $this->formatNotification($notification)
        );

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return response()->json([
                'status' => 404,
                'message' => 'Thong bao khong ton tai.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'status' => 200,
            'message' => 'Da danh dau da doc',
            'data' => $this->formatNotification($notification->refresh()),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'status' => 200,
            'message' => 'Da danh dau tat ca da doc',
            'data' => [
                'unread_count' => 0,
            ],
        ]);
    }

    private function formatNotification(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->data['type'] ?? class_basename($notification->type),
            'title' => $notification->data['title'] ?? null,
            'message' => $notification->data['message'] ?? null,
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toISOString(),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
}
