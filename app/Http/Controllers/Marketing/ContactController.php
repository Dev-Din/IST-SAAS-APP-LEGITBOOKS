<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Services\Mail\PHPMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function showForm()
    {
        return view('marketing.contact');
    }

    public function submitForm(Request $request, PHPMailerService $mailer)
    {
        // Log incoming request
        Log::info('=== CONTACT FORM SUBMISSION START ===', [
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_data' => $request->except(['_token']),
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
        ]);

        try {
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'email'   => 'required|email|max:255',
                'company' => 'nullable|string|max:255',
                'phone'   => 'nullable|string|max:50',
                'message' => 'required|string|min:5',
            ]);

            Log::info('Form validation passed', [
                'validated_data' => $validated,
            ]);

            // Create fingerprint to detect duplicate submissions
            $fingerprint = sha1(
                $validated['email'] . '|' .
                ($validated['company'] ?? '') . '|' .
                ($validated['phone'] ?? '') . '|' .
                $validated['message']
            );

            // Check if this exact submission was sent recently (within 60 seconds)
            $cacheKey = 'contact_form_' . $fingerprint;
            if (Cache::has($cacheKey)) {
                Log::warning('Duplicate contact form submission detected', [
                    'fingerprint' => $fingerprint,
                    'email' => $validated['email'],
                ]);

                return redirect()->route('marketing.contact')
                    ->with('status', 'We already received your message. Please wait a moment before sending again.');
            }

            // Store fingerprint in cache for 60 seconds
            Cache::put($cacheKey, true, now()->addSeconds(60));

            // Create submission record
            $submission = ContactSubmission::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'company' => $validated['company'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'message' => $validated['message'],
                'mail_status' => null,
            ]);

            Log::info('Submission record created', [
                'submission_id' => $submission->id,
            ]);

            // Render internal notification email template
            $html = view('emails.contact.internal', [
                'submission' => $submission
            ])->render();

            // Send email using PHPMailer
            $supportEmail = env('CONTACT_SUPPORT_EMAIL', 'nurudiin222@gmail.com');
            
            Log::info('Attempting to send notification email via PHPMailer', [
                'submission_id' => $submission->id,
                'recipient' => $supportEmail,
            ]);

            $mailSent = $mailer->send([
                'to'       => $supportEmail,
                'subject'  => "New LegitBooks Enquiry — {$submission->name}",
                'html'     => $html,
                'reply_to' => $submission->email,
            ]);

            $mailStatus = $mailSent ? 'sent' : 'failed';
            $submission->mail_status = $mailStatus;
            $submission->save();

            Log::info('Notification email sending completed', [
                'submission_id' => $submission->id,
                'mail_status' => $mailStatus,
            ]);

            Log::info('=== CONTACT FORM SUBMISSION END (SUCCESS) ===', [
                'submission_id' => $submission->id,
                'mail_status' => $mailStatus,
            ]);

            // Return JSON if AJAX request, otherwise redirect
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for your message! We\'ll get back to you soon.',
                ]);
            }

            return redirect()->route('marketing.contact')
                ->with('success', 'Thank you for your message! We\'ll get back to you soon.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Form validation failed', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Contact form submission exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

}

