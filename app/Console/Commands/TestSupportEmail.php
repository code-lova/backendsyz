<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSupportEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:support-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test support email configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Testing support email configuration...');
        $this->info('Support Address: ' . config('mail.support.address'));
        $this->info('Support Name: ' . config('mail.support.name'));

        try {
            Mail::raw('This is a test email from support system.', function ($message) use ($email) {
                $message->to($email)
                       ->from(
                           config('mail.from.address'), // Use authenticated email
                           config('mail.support.name')  // With support name
                       )
                       ->replyTo(
                           config('mail.support.address'), // Reply goes to support
                           config('mail.support.name')
                       )
                       ->subject('Test Support Email');
            });

            $this->info('✅ Test email sent successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Error sending email: ' . $e->getMessage());
        }
    }
}
