<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a new notification for a user.
     *
     * @param string $userUuid
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array|null $data
     * @return Notification|null
     */
    public function createNotification(string $userUuid, string $type, string $title, string $message, ?array $data = null): ?Notification
    {
        try {
            return Notification::create([
                'user_uuid' => $userUuid,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'user_uuid' => $userUuid,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create notification for booking assignment to client.
     */
    public function notifyClientBookingAssigned(string $clientUuid, string $bookingReference, string $healthWorkerName): ?Notification
    {
        return $this->createNotification(
            $clientUuid,
            'booking_assigned',
            'Booking Assigned',
            "Your booking request #{$bookingReference} has been assigned to {$healthWorkerName}.",
            [
                'booking_reference' => $bookingReference,
                'health_worker_name' => $healthWorkerName
            ]
        );
    }

    /**
     * Create notification for support reply to client.
     */
    public function notifyClientSupportReply(string $clientUuid, string $ticketReference): ?Notification
    {
        return $this->createNotification(
            $clientUuid,
            'support_reply',
            'Support Reply Received',
            "You have received a reply to your support ticket #{$ticketReference}.",
            [
                'ticket_reference' => $ticketReference
            ]
        );
    }

    /**
     * Create notification for new booking assignment to health worker.
     */
    public function notifyHealthWorkerNewBooking(string $healthWorkerUuid, string $bookingReference, string $clientName): ?Notification
    {
        return $this->createNotification(
            $healthWorkerUuid,
            'new_booking_assigned',
            'New Booking Assignment',
            "You have been assigned a new booking #{$bookingReference} from {$clientName}.",
            [
                'booking_reference' => $bookingReference,
                'client_name' => $clientName
            ]
        );
    }

    /**
     * Create notification for support reply to health worker.
     */
    public function notifyHealthWorkerSupportReply(string $healthWorkerUuid, ?string $supportReference): ?Notification
    {
        // Handle null or empty reference
        if (empty($supportReference)) {
            Log::warning('Cannot create health worker support reply notification - empty reference', [
                'health_worker_uuid' => $healthWorkerUuid
            ]);
            return null;
        }

        return $this->createNotification(
            $healthWorkerUuid,
            'support_reply',
            'Support Reply Received',
            "You have received a reply to your support message #{$supportReference}.",
            [
                'support_reference' => $supportReference
            ]
        );
    }

    /**
     * Create notification for admin about new booking request.
     */
    public function notifyAdminNewBookingRequest(string $bookingReference, string $clientName): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'new_booking_request',
                'New Booking Request',
                "New booking request #{$bookingReference} received from {$clientName}.",
                [
                    'booking_reference' => $bookingReference,
                    'client_name' => $clientName
                ]
            );
        }
    }

    /**
     * Create notification for admin about new client support ticket.
     */
    public function notifyAdminNewClientSupport(string $ticketReference, string $clientName): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'new_client_support',
                'New Client Support Message',
                "New support ticket #{$ticketReference} received from {$clientName}.",
                [
                    'ticket_reference' => $ticketReference,
                    'client_name' => $clientName
                ]
            );
        }
    }

    /**
     * Create notification for admin about client reply to support ticket.
     */
    public function notifyAdminClientSupportReply(string $ticketReference, string $clientName): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'client_support_reply',
                'Client Support Reply',
                "New reply to support ticket #{$ticketReference} from {$clientName}.",
                [
                    'ticket_reference' => $ticketReference,
                    'client_name' => $clientName
                ]
            );
        }
    }

    /**
     * Create notification for admin about health worker booking confirmation.
     */
    public function notifyAdminBookingConfirmed(string $bookingReference, string $healthWorkerName, string $clientName): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'booking_confirmed',
                'Booking Confirmed by Health Worker',
                "Booking #{$bookingReference} has been confirmed by {$healthWorkerName} for client {$clientName}.",
                [
                    'booking_reference' => $bookingReference,
                    'health_worker_name' => $healthWorkerName,
                    'client_name' => $clientName
                ]
            );
        }
    }

    /**
     * Create notification for admin about health worker starting a booking.
     */
    public function notifyAdminBookingStarted(string $bookingReference, string $healthWorkerName, string $clientName): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'booking_started',
                'Booking Started by Health Worker',
                "Booking #{$bookingReference} has been started by {$healthWorkerName} for client {$clientName}.",
                [
                    'booking_reference' => $bookingReference,
                    'health_worker_name' => $healthWorkerName,
                    'client_name' => $clientName
                ]
            );
        }
    }

    /**
     * Create notification for admin about new health worker support message.
     */
    public function notifyAdminNewHealthWorkerSupport(string $supportReference, string $healthWorkerName): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'new_healthworker_support',
                'New Health Worker Support Message',
                "New support message #{$supportReference} received from {$healthWorkerName}.",
                [
                    'support_reference' => $supportReference,
                    'health_worker_name' => $healthWorkerName
                ]
            );
        }
    }

    /**
     * Create notification for admin about new user registration.
     */
    public function notifyAdminNewUserRegistration(string $userName, string $userRole, string $userEmail): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'new_user_registration',
                'New User Registration',
                "New {$userRole} registered: {$userName} ({$userEmail}).",
                [
                    'user_name' => $userName,
                    'user_role' => $userRole,
                    'user_email' => $userEmail
                ]
            );
        }
    }

    /**
     * Notify admins when a user successfully verifies their email.
     */
    public function notifyAdminUserEmailVerified(string $userName, string $userRole, string $userEmail): void
    {
        $adminUsers = User::where('role', 'admin')->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin->uuid,
                'user_email_verified',
                'User Email Verified',
                "{$userRole} {$userName} has successfully verified their email address ({$userEmail}).",
                [
                    'user_name' => $userName,
                    'user_role' => $userRole,
                    'user_email' => $userEmail
                ]
            );
        }
    }

    /**
     * Get notifications for a user with pagination.
     */
    public function getUserNotifications(string $userUuid, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::where('user_uuid', $userUuid)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unread notifications count for a user.
     */
    public function getUnreadCount(string $userUuid): int
    {
        return Notification::where('user_uuid', $userUuid)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $notificationId): bool
    {
        try {
            $notification = Notification::find($notificationId);
            return $notification ? $notification->markAsRead() : false;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(string $userUuid): bool
    {
        try {
            return Notification::where('user_uuid', $userUuid)
                ->whereNull('read_at')
                ->update(['read_at' => now()]) !== false;
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_uuid' => $userUuid,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete old notifications (older than specified days).
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        try {
            return Notification::where('created_at', '<', now()->subDays($days))
                ->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete old notifications', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Delete read notifications older than specified days.
     */
    public function deleteOldReadNotifications(int $days = 7): int
    {
        try {
            return Notification::whereNotNull('read_at')
                ->where('read_at', '<', now()->subDays($days))
                ->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete old read notifications', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Clean up notifications by type and age.
     */
    public function cleanupNotificationsByType(string $type, int $days = 30): int
    {
        try {
            return Notification::where('type', $type)
                ->where('created_at', '<', now()->subDays($days))
                ->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete notifications by type', [
                'type' => $type,
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Automatically cleanup old notifications when creating new ones.
     * This can be called periodically to maintain database size.
     */
    public function autoCleanup(): void
    {
        try {
            // Delete read notifications older than 7 days
            $this->deleteOldReadNotifications(7);

            // Delete all notifications older than 30 days
            $this->deleteOldNotifications(30);

            Log::info('Auto cleanup completed successfully');
        } catch (\Exception $e) {
            Log::error('Auto cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
