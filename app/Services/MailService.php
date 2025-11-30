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
        $inviter = $invitation->inviter;
        $tenantName = 'LegitBooks'; // Default, can be enhanced to get from tenant
        
        $acceptUrl = route('admin.invite.accept', $invitation->token);
        
        // Render HTML email
        $html = View::make('emails.admin.invite', [
            'invitation' => $invitation,
            'inviter' => $inviter,
            'tenantName' => $tenantName,
            'tempPassword' => $tempPassword,
            'acceptUrl' => $acceptUrl,
        ])->render();

        // Render plain text email
        $text = View::make('emails.admin.invite-text', [
            'invitation' => $invitation,
            'inviter' => $inviter,
            'tenantName' => $tenantName,
            'tempPassword' => $tempPassword,
            'acceptUrl' => $acceptUrl,
        ])->render();

        return $this->phpMailer->send([
            'to' => $invitation->email,
            'subject' => "You've been invited to join {$tenantName} as an Admin",
            'html' => $html,
            'text' => $text,
            'from_name' => "{$tenantName} via LegitBooks",
        ]);
    }
}

