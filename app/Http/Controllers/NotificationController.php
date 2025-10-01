<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get paginated notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $request->validate([
                'per_page' => 'nullable|integer|min:5|max:50'
            ]);

            $perPage = $request->get('per_page', 15);

            $notifications = $this->notificationService->getUserNotifications($user->uuid, $perPage);

            return response()->json([
                'status' => 'Success',
                'message' => 'Notifications retrieved successfully.',
                'data' => [
                    'notifications' => $notifications->items(),
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'last_page' => $notifications->lastPage(),
                        'from' => $notifications->firstItem(),
                        'to' => $notifications->lastItem()
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve notifications', [
                'user_uuid' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to retrieve notifications. Please try again.'
            ], 500);
        }
    }

    /**
     * Get unread notifications count for the authenticated user.
     *
     * @return JsonResponse
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            $user = Auth::user();
            $unreadCount = $this->notificationService->getUnreadCount($user->uuid);

            return response()->json([
                'status' => 'Success',
                'message' => 'Unread count retrieved successfully.',
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve unread count', [
                'user_uuid' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to retrieve unread count. Please try again.'
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'notification_id' => 'required|string|exists:notifications,id'
            ]);

            $user = Auth::user();
            $notificationId = $request->get('notification_id');

            // Verify the notification belongs to the authenticated user
            $notification = Notification::where('id', $notificationId)
                ->where('user_uuid', $user->uuid)
                ->first();

            if (!$notification) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Notification not found or access denied.'
                ], 404);
            }

            $success = $this->notificationService->markAsRead($notificationId);

            if ($success) {
                return response()->json([
                    'status' => 'Success',
                    'message' => 'Notification marked as read successfully.'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Failed to mark notification as read.'
                ], 500);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'user_uuid' => Auth::id(),
                'notification_id' => $request->get('notification_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to mark notification as read. Please try again.'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the authenticated user.
     *
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            $success = $this->notificationService->markAllAsRead($user->uuid);

            if ($success) {
                return response()->json([
                    'status' => 'Success',
                    'message' => 'All notifications marked as read successfully.'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Failed to mark all notifications as read.'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_uuid' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to mark all notifications as read. Please try again.'
            ], 500);
        }
    }

    /**
     * Get latest notifications (unread first, then recent read ones).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLatestNotifications(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $request->validate([
                'limit' => 'nullable|integer|min:5|max:20'
            ]);

            $limit = $request->get('limit', 3);

            $notifications = Notification::where('user_uuid', $user->uuid)
                ->orderByRaw('read_at IS NULL DESC, created_at DESC')
                ->limit($limit)
                ->get();

            $unreadCount = $this->notificationService->getUnreadCount($user->uuid);

            return response()->json([
                'status' => 'Success',
                'message' => 'Latest notifications retrieved successfully.',
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount,
                    'total_shown' => $notifications->count()
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve latest notifications', [
                'user_uuid' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to retrieve latest notifications. Please try again.'
            ], 500);
        }
    }
}
