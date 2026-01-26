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
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'E',
            'location' => 'app/Services/Mail/PHPMailerService.php:send',
            'message' => 'PHPMailerService->send entry',
            'data' => [
                'has_to' => !empty($data['to']),
                'has_subject' => !empty($data['subject']),
                'has_html' => !empty($data['html']),
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        // Validate required fields
        if (empty($data['to']) || empty($data['subject']) || empty($data['html'])) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'E',
                'location' => 'app/Services/Mail/PHPMailerService.php:send',
                'message' => 'Missing required fields, returning false',
                'data' => [
                    'provided_keys' => array_keys($data),
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

            Log::error('PHPMailer: Missing required fields', [
                'provided_keys' => array_keys($data),
            ]);

            return false;
        }

        $smtpConfig = config('mailers.smtp');

        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'app/Services/Mail/PHPMailerService.php:send',
            'message' => 'SMTP config loaded',
            'data' => [
                'host' => $smtpConfig['host'] ?? 'not set',
                'port' => $smtpConfig['port'] ?? 'not set',
                'has_username' => !empty($smtpConfig['username']),
                'has_password' => !empty($smtpConfig['password']),
                'encryption' => $smtpConfig['encryption'] ?? 'not set',
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        // Validate SMTP configuration
        if (empty($smtpConfig['username']) || empty($smtpConfig['password'])) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'A',
                'location' => 'app/Services/Mail/PHPMailerService.php:send',
                'message' => 'SMTP credentials missing, returning false',
                'data' => [
                    'host' => $smtpConfig['host'] ?? 'not set',
                    'username_set' => ! empty($smtpConfig['username']),
                    'password_set' => ! empty($smtpConfig['password']),
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

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

            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B',
                'location' => 'app/Services/Mail/PHPMailerService.php:send',
                'message' => 'Before calling PHPMailer->send()',
                'data' => [
                    'to' => $data['to'],
                    'host' => $mail->Host,
                    'port' => $mail->Port,
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

            // Send email
            $mail->send();

            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B',
                'location' => 'app/Services/Mail/PHPMailerService.php:send',
                'message' => 'PHPMailer->send() completed successfully',
                'data' => [],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

            Log::info('PHPMailer: Email sent successfully', [
                'to' => $data['to'],
                'subject' => $data['subject'],
            ]);

            return true;
        } catch (Exception $e) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B',
                'location' => 'app/Services/Mail/PHPMailerService.php:send',
                'message' => 'PHPMailer Exception caught',
                'data' => [
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                    'phpmailer_error_info' => $mail->ErrorInfo ?? 'not available',
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

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
