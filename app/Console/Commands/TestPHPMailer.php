<?php

namespace App\Console\Commands;

use App\Services\Mail\PHPMailerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPHPMailer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-phpmailer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PHPMailer service by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle(PHPMailerService $mailer): int
    {
        $this->info('Testing PHPMailer service...');

        $supportEmail = env('CONTACT_SUPPORT_EMAIL', 'nurudiin222@gmail.com');

        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>PHPMailer Test</title>
            </head>
            <body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;">
                <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h1 style="color: #392a26; margin-top: 0;">PHPMailer Test Email</h1>
                    <p>This is a test email from <strong>LegitBooks</strong> to verify that PHPMailer is working correctly with Gmail SMTP.</p>
                    <p>If you received this email, the integration is successful!</p>
                    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
                    <p style="color: #666; font-size: 12px; margin-bottom: 0;">
                        Sent at: ' . now()->format('d/m/Y H:i:s') . '<br>
                        From: LegitBooks Application
                    </p>
                </div>
            </body>
            </html>
        ';

        $text = "PHPMailer Test Email\n\nThis is a test email from LegitBooks to verify that PHPMailer is working correctly with Gmail SMTP.\n\nIf you received this email, the integration is successful!\n\nSent at: " . now()->format('d/m/Y H:i:s') . "\nFrom: LegitBooks Application";

        $this->info("Sending test email to: {$supportEmail}");

        $result = $mailer->send([
            'to'        => $supportEmail,
            'subject'   => 'PHPMailer test from LegitBooks',
            'html'      => $html,
            'text'      => $text,
            'from_name' => 'LegitBooks',
        ]);

        if ($result) {
            $this->info('✓ Email sent successfully!');
            $this->info("Check your inbox at: {$supportEmail}");
            return Command::SUCCESS;
        } else {
            $this->error('✗ Failed to send email');
            $this->error('Check the logs for detailed error information: storage/logs/laravel.log');
            Log::error('PHPMailer test command failed');
            return Command::FAILURE;
        }
    }
}

