<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class PHPMailerService
{
    /**
     * Send email using PHPMailer with Gmail SMTP
     *
     * @param  array  $data  Required keys: 'to', 'subject', 'html'. Optional: 'text', 'reply_to', 'from_name', 'attachments'
     */
    public function send(array $data): bool
    {
        // Validate required fields
        if (empty($data['to']) || empty($data['subject']) || empty($data['html'])) {
            Log::error('PHPMailer: Missing required fields', [
                'provided_keys' => array_keys($data),
            ]);

            return false;
        }

        $smtpConfig = config('mailers.smtp');

        // Validate SMTP configuration
        if (empty($smtpConfig['username']) || empty($smtpConfig['password'])) {
            Log::error('PHPMailer: SMTP credentials not configured', [
                'host' => $smtpConfig['host'] ?? 'not set',
                'username_set' => ! empty($smtpConfig['username']),
                'password_set' => ! empty($smtpConfig['password']),
            ]);

            return false;
        }

        $mail = new PHPMailer(true);

        // Set UTF-8 encoding for proper character handling
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpConfig['username'];
            $mail->Password = $smtpConfig['password'];
            $mail->SMTPSecure = $smtpConfig['encryption'];
            $mail->Port = $smtpConfig['port'];

            // Enable verbose debug output (only in debug mode)
            if (config('app.debug')) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function ($str, $level) {
                    Log::debug("PHPMailer Debug: $str");
                };
            }

            // Recipients
            $mail->setFrom(
                $smtpConfig['from']['address'],
                $data['from_name'] ?? $smtpConfig['from']['name']
            );
            $mail->addAddress($data['to']);

            // Reply-To (optional)
            if (! empty($data['reply_to'])) {
                $mail->addReplyTo($data['reply_to']);
            }

            // Attachments (optional)
            if (! empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    } else {
                        Log::warning('PHPMailer: Attachment file not found', [
                            'file' => $attachment,
                        ]);
                    }
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $data['subject'];
            $mail->Body = $data['html'];
            $mail->AltBody = $data['text'] ?? strip_tags($data['html']);

            // Send email
            $mail->send();

            Log::info('PHPMailer: Email sent successfully', [
                'to' => $data['to'],
                'subject' => $data['subject'],
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('PHPMailer: Failed to send email', [
                'to' => $data['to'],
                'subject' => $data['subject'],
                'error' => $mail->ErrorInfo,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
