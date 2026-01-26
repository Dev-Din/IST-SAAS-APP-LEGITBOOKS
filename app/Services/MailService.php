<?php

namespace App\Services;

use App\Models\AdminInvitation;
use App\Services\Mail\PHPMailerService;
use Illuminate\Support\Facades\View;

class MailService
{
    public function __construct(
        protected PHPMailerService $phpMailer
    ) {}

    /**
     * Send admin invitation email
     */
    public function sendAdminInvite(AdminInvitation $invitation, string $tempPassword): bool
    {
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B',
            'location' => 'app/Services/MailService.php:sendAdminInvite',
            'message' => 'sendAdminInvite entry',
            'data' => [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        $inviter = $invitation->inviter;
        $tenantName = 'LegitBooks'; // Default, can be enhanced to get from tenant

        $acceptUrl = route('admin.invite.accept', $invitation->token);

        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C',
            'location' => 'app/Services/MailService.php:sendAdminInvite',
            'message' => 'Before rendering HTML template',
            'data' => [],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        // Render HTML email
        try {
            $html = View::make('emails.admin.invite', [
                'invitation' => $invitation,
                'inviter' => $inviter,
                'tenantName' => $tenantName,
                'tempPassword' => $tempPassword,
                'acceptUrl' => $acceptUrl,
            ])->render();
        } catch (\Exception $e) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'C',
                'location' => 'app/Services/MailService.php:sendAdminInvite',
                'message' => 'Exception rendering HTML template',
                'data' => [
                    'exception' => $e->getMessage(),
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion
            throw $e;
        }

        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C',
            'location' => 'app/Services/MailService.php:sendAdminInvite',
            'message' => 'HTML template rendered successfully',
            'data' => [
                'html_length' => strlen($html),
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        // Render plain text email
        try {
            $text = View::make('emails.admin.invite-text', [
                'invitation' => $invitation,
                'inviter' => $inviter,
                'tenantName' => $tenantName,
                'tempPassword' => $tempPassword,
                'acceptUrl' => $acceptUrl,
            ])->render();
        } catch (\Exception $e) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'C',
                'location' => 'app/Services/MailService.php:sendAdminInvite',
                'message' => 'Exception rendering text template',
                'data' => [
                    'exception' => $e->getMessage(),
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion
            throw $e;
        }

        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'D',
            'location' => 'app/Services/MailService.php:sendAdminInvite',
            'message' => 'Before calling PHPMailerService->send',
            'data' => [],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        $result = $this->phpMailer->send([
            'to' => $invitation->email,
            'subject' => "You've been invited to join {$tenantName} as an Admin",
            'html' => $html,
            'text' => $text,
            'from_name' => $tenantName,
        ]);

        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'D',
            'location' => 'app/Services/MailService.php:sendAdminInvite',
            'message' => 'PHPMailerService->send returned',
            'data' => [
                'result' => $result,
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        return $result;
    }
}
