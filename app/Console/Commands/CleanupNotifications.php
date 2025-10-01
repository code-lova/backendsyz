<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup 
                           {--days=30 : Number of days to keep notifications}
                           {--read-days=7 : Number of days to keep read notifications}
                           {--type= : Clean up specific notification type}
                           {--auto : Run automatic cleanup with preset rules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old notifications with various cleanup options';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            if ($this->option('auto')) {
                return $this->runAutoCleanup();
            }

            if ($type = $this->option('type')) {
                return $this->cleanupByType($type);
            }

            return $this->runManualCleanup();

        } catch (\Exception $e) {
            $this->error("Failed to cleanup notifications: " . $e->getMessage());
            Log::error("Notification cleanup failed", [
                'error' => $e->getMessage()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Run automatic cleanup with preset rules.
     */
    private function runAutoCleanup(): int
    {
        $this->info("Running automatic cleanup with preset rules...");
        
        $this->notificationService->autoCleanup();
        $this->info("Automatic cleanup completed successfully.");
        
        return Command::SUCCESS;
    }

    /**
     * Clean up notifications by specific type.
     */
    private function cleanupByType(string $type): int
    {
        $days = (int) $this->option('days');
        
        if ($days < 1) {
            $this->error('Days must be a positive number.');
            return Command::FAILURE;
        }

        $this->info("Cleaning up '{$type}' notifications older than {$days} days...");
        
        $deletedCount = $this->notificationService->cleanupNotificationsByType($type, $days);
        
        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} '{$type}' notifications.");
        } else {
            $this->info("No '{$type}' notifications found to delete.");
        }

        return Command::SUCCESS;
    }

    /**
     * Run manual cleanup with user-specified parameters.
     */
    private function runManualCleanup(): int
    {
        $days = (int) $this->option('days');
        $readDays = (int) $this->option('read-days');
        
        if ($days < 1 || $readDays < 1) {
            $this->error('Days must be positive numbers.');
            return Command::FAILURE;
        }

        $totalDeleted = 0;

        // Clean up read notifications first
        $this->info("Cleaning up read notifications older than {$readDays} days...");
        $readDeleted = $this->notificationService->deleteOldReadNotifications($readDays);
        $totalDeleted += $readDeleted;

        if ($readDeleted > 0) {
            $this->info("Deleted {$readDeleted} old read notifications.");
        }

        // Clean up all notifications
        $this->info("Cleaning up all notifications older than {$days} days...");
        $allDeleted = $this->notificationService->deleteOldNotifications($days);
        $totalDeleted += $allDeleted;

        if ($allDeleted > 0) {
            $this->info("Deleted {$allDeleted} old notifications.");
        }

        if ($totalDeleted > 0) {
            $this->info("Total notifications deleted: {$totalDeleted}");
            Log::info("Manual notification cleanup completed", [
                'total_deleted' => $totalDeleted,
                'read_days_threshold' => $readDays,
                'all_days_threshold' => $days
            ]);
        } else {
            $this->info("No old notifications found to delete.");
        }

        return Command::SUCCESS;
    }
}
