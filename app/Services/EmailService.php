<?php

namespace App\Services;

use App\Services\Mail\PHPMailerService;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected PHPMailerService $phpMailer;

    public function __construct(PHPMailerService $phpMailer)
    {
        $this->phpMailer = $phpMailer;
    }

    public function send(string $to, string $subject, string $html): bool
    {
        return $this->phpMailer->send([
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'text' => strip_tags($html),
        ]);
    }
}
