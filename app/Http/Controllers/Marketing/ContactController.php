<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function showForm()
    {
        return view('marketing.contact');
    }

    public function submitForm(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            // Try to send email via Mailgun if configured
            $supportEmail = config('mail.support_email', env('MAIL_SUPPORT_EMAIL', 'support@legitbooks.com'));
            
            if (config('mail.default') === 'mailgun' && config('mail.mailers.mailgun.domain')) {
                $company = $validated['company'] ?? 'N/A';
                Mail::raw(
                    "Name: {$validated['name']}\n" .
                    "Email: {$validated['email']}\n" .
                    "Company: {$company}\n\n" .
                    "Message:\n{$validated['message']}",
                    function ($message) use ($supportEmail, $validated) {
                        $message->to($supportEmail)
                            ->subject('Contact Form Submission from ' . $validated['name'])
                            ->replyTo($validated['email']);
                    }
                );
            } else {
                // Log to file if email not configured
                Log::info('Contact form submission', $validated);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('Failed to send contact form email', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);
        }

        return redirect()->route('marketing.contact')
            ->with('success', 'Thank you for your message! We\'ll get back to you soon.');
    }
}

