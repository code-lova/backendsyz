<?php

namespace App\Console\Commands;

use App\Mail\NewBookingRequest;
use App\Mail\VerifyEmailCodeMail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-templates {--type=all : Test booking, verify, welcome, or all templates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all email templates (booking, verification, welcome) by generating HTML files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');

        $this->info('🧪 Testing Email Templates...');
        $this->newLine();

        // Test different template types based on option
        switch ($type) {
            case 'booking':
                $this->testBookingRequest($type);
                $this->testBookingStatusNotification($type);
                break;
            case 'verify':
                $this->testVerificationEmail();
                break;
            case 'welcome':
                $this->testWelcomeEmail();
                break;
            case 'all':
            default:
                $this->testBookingRequest($type);
                $this->testBookingStatusNotification($type);
                $this->testVerificationEmail();
                $this->testWelcomeEmail();
                break;
        }

        $this->newLine();
        $this->info('✅ All email template tests completed!');
        return Command::SUCCESS;
    }

    private function testBookingRequest(string $type): void
    {
        $this->info('📧 Testing BOOKING REQUEST templates...');

        // Sample booking data
        $sampleData = [
            'booking_reference' => 'BOOK-2025-001234',
            'client_name' => 'John Doe',
            'client_email' => 'john.doe@example.com',
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-05',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'start_time_period' => 'AM',
            'end_time_period' => 'PM',
            'care_type' => 'Home Care',
            'care_duration' => 'Multi-day',
            'care_duration_value' => '8',
            'requesting_for' => 'Myself',
            'accommodation' => 'Stay-in',
            'meal' => 'Yes',
            'num_of_meals' => '3',
            'special_notes' => 'Patient requires assistance with mobility and medication reminders.',
        ];

        if ($type === 'client' || $type === 'both') {
            $this->testBookingRequestClient($sampleData);
        }

        if ($type === 'admin' || $type === 'both') {
            $this->testBookingRequestAdmin($sampleData);
        }
    }

    private function testBookingStatusNotification(string $type): void
    {
        $this->info('📧 Testing BOOKING STATUS NOTIFICATION templates...');

        // Sample status notification data
        $sampleData = [
            'booking_reference' => 'BOOK-2025-002468',
            'status' => 'Confirmed',
            'client_name' => 'Sarah Johnson',
            'healthworker_name' => 'Dr. Michael Smith',
            'start_date' => '2025-10-20',
            'end_date' => '2025-10-25',
            'start_time' => '08:30:00',
            'end_time' => '16:30:00',
            'start_time_period' => 'AM',
            'end_time_period' => 'PM',
            'care_type' => 'Personal Care',
            'care_duration' => 'Multi-day',
            'care_duration_value' => '8',
            'accommodation' => 'Stay-in',
            'meal' => 'Yes',
            'num_of_meals' => '3',
            'processed_at' => now()->format('Y-m-d H:i:s')
        ];

        if ($type === 'client' || $type === 'both') {
            $this->testStatusNotificationClient($sampleData);
        }

        if ($type === 'admin' || $type === 'both') {
            $this->testStatusNotificationAdmin($sampleData);
        }

        if ($type === 'healthworker' || $type === 'both') {
            $this->testStatusNotificationHealthWorker($sampleData);
        }
    }

    // Booking Request Template Tests
    private function testBookingRequestClient(array $data): void
    {
        try {
            $html = view('emails.booking_request', $data)->render();

            // Save to temporary file for inspection
            $filePath = storage_path('app/test_booking_request_client.html');
            file_put_contents($filePath, $html);

            $this->info("  ✓ Booking Request - Client template rendered successfully");
            $this->info("    Saved to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("  ✗ Booking Request - Client template failed: " . $e->getMessage());
        }
    }

    private function testBookingRequestAdmin(array $data): void
    {
        try {
            $adminData = array_merge($data, ['recipient_type' => 'admin']);
            $html = view('emails.booking_request', $adminData)->render();

            // Save to temporary file for inspection
            $filePath = storage_path('app/test_booking_request_admin.html');
            file_put_contents($filePath, $html);

            $this->info("  ✓ Booking Request - Admin template rendered successfully");
            $this->info("    Saved to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("  ✗ Booking Request - Admin template failed: " . $e->getMessage());
        }
    }

    // Booking Status Notification Template Tests
    private function testStatusNotificationClient(array $data): void
    {
        try {
            $html = view('emails.booking_status_notification', $data)->render();

            // Save to temporary file for inspection
            $filePath = storage_path('app/test_status_notification_client.html');
            file_put_contents($filePath, $html);

            $this->info("  ✓ Status Notification - Client template rendered successfully");
            $this->info("    Saved to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("  ✗ Status Notification - Client template failed: " . $e->getMessage());
        }
    }

    private function testStatusNotificationAdmin(array $data): void
    {
        try {
            $adminData = array_merge($data, ['is_for_admin' => true]);
            $html = view('emails.booking_status_notification', $adminData)->render();

            // Save to temporary file for inspection
            $filePath = storage_path('app/test_status_notification_admin.html');
            file_put_contents($filePath, $html);

            $this->info("  ✓ Status Notification - Admin template rendered successfully");
            $this->info("    Saved to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("  ✗ Status Notification - Admin template failed: " . $e->getMessage());
        }
    }

    private function testStatusNotificationHealthWorker(array $data): void
    {
        try {
            $hwData = array_merge($data, ['is_for_healthworker' => true]);
            $html = view('emails.booking_status_notification', $hwData)->render();

            // Save to temporary file for inspection
            $filePath = storage_path('app/test_status_notification_healthworker.html');
            file_put_contents($filePath, $html);

            $this->info("  ✓ Status Notification - Health Worker template rendered successfully");
            $this->info("    Saved to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("  ✗ Status Notification - Health Worker template failed: " . $e->getMessage());
        }
    }

    private function testVerificationEmail(): void
    {
        $this->info('📧 Testing EMAIL VERIFICATION templates...');

        // Create a sample user for testing
        $sampleUser = new User([
            'name' => 'Dr. John Smith',
            'email' => 'john.smith@example.com',
            'role' => 'healthworker',
            'practitioner' => 'doctor',
            'email_verification_code' => 'ABC123',
        ]);

        try {
            $mail = new VerifyEmailCodeMail($sampleUser);
            $html = $mail->render();

            $filePath = storage_path('app/test_verification_email.html');
            file_put_contents($filePath, $html);

            $this->info("  ✓ Email verification template rendered successfully");
            $this->info("    Saved to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("  ✗ Email verification template failed: " . $e->getMessage());
        }
    }

    private function testWelcomeEmail(): void
    {
        $this->info('📧 Testing WELCOME EMAIL templates...');

        // Test for different user roles
        $roles = ['client', 'healthworker', 'admin'];

        foreach ($roles as $role) {
            try {
                $sampleUser = new User([
                    'name' => ucfirst($role) . ' Test User',
                    'email' => "{$role}@example.com",
                    'role' => $role,
                    'practitioner' => $role === 'healthworker' ? 'doctor' : null,
                ]);

                $mail = new WelcomeEmail($sampleUser);
                $html = $mail->render();

                $filePath = storage_path("app/test_welcome_email_{$role}.html");
                file_put_contents($filePath, $html);

                $this->info("  ✓ Welcome email template ({$role}) rendered successfully");
                $this->info("    Saved to: {$filePath}");

            } catch (\Exception $e) {
                $this->error("  ✗ Welcome email template ({$role}) failed: " . $e->getMessage());
            }
        }
    }
}
