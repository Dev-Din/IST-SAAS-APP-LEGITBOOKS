<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailService
{
    public function send(string $to, string $subject, string $html): bool
    {
        $domain = config('services.mailgun.domain');
        $apiKey = config('services.mailgun.secret');
        $from = 'LegitBooks <no-reply@' . ($domain ?? 'legitbooks.test') . '>';

        if (!$domain || !$apiKey) {
            return false;
        }

        $response = Http::withBasicAuth('api', $apiKey)
            ->asForm()
            ->post("https://api.mailgun.net/v3/{$domain}/messages", [
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
            ]);

        return $response->successful();
    }
}
