<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Mail\ContactNotification;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function showForm()
    {
        return view('marketing.contact');
    }

    public function submitForm(Request $request)
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
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'company' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'message' => 'required|string|max:5000',
            ]);

            Log::info('Form validation passed', [
                'validated_data' => $validated,
            ]);

            // Create submission record
            $submission = ContactSubmission::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'company' => $validated['company'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'message' => $validated['message'],
                'web3forms_status' => null,
                'mail_status' => null,
            ]);

            Log::info('Submission record created', [
                'submission_id' => $submission->id,
            ]);

            // Submit to Web3Forms (server-side only)
            Log::info('Attempting Web3Forms API submission', [
                'submission_id' => $submission->id,
            ]);
            $web3formsStatus = $this->submitToWeb3Forms($validated);
            $submission->web3forms_status = $web3formsStatus;
            $submission->save();

            Log::info('Web3Forms submission completed', [
                'submission_id' => $submission->id,
                'web3forms_status' => $web3formsStatus,
            ]);

            // Send internal notification email
            Log::info('Attempting to send notification email', [
                'submission_id' => $submission->id,
                'recipient' => config('mail.from.address', 'support@legitbooks.com'),
            ]);
            $mailStatus = $this->sendNotificationEmail($submission);
            $submission->mail_status = $mailStatus;
            $submission->save();

            Log::info('Notification email sending completed', [
                'submission_id' => $submission->id,
                'mail_status' => $mailStatus,
            ]);

            Log::info('=== CONTACT FORM SUBMISSION END (SUCCESS) ===', [
                'submission_id' => $submission->id,
                'web3forms_status' => $web3formsStatus,
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

    /**
     * Submit form data to Web3Forms API
     * Reference: https://docs.web3forms.com/
     */
    private function submitToWeb3Forms(array $data): string
    {
        $apiKey = env('WEB3FORMS_API_KEY');
        
        Log::info('Web3Forms API Key Check', [
            'api_key_present' => !empty($apiKey),
            'api_key_length' => $apiKey ? strlen($apiKey) : 0,
            'api_key_preview' => $apiKey ? substr($apiKey, 0, 8) . '...' : 'NOT SET',
        ]);
        
        if (!$apiKey) {
            Log::error('Web3Forms API key not configured. Please set WEB3FORMS_API_KEY in .env file');
            return 'failed';
        }

        try {
            // Prepare payload according to Web3Forms API requirements
            // Web3Forms accepts form-urlencoded data (default for Laravel Http::asForm())
            $payload = [
                'access_key' => $apiKey,
                'name' => $data['name'],
                'email' => $data['email'],
                'message' => $data['message'],
            ];

            // Add optional fields only if they have values
            if (!empty($data['company'])) {
                $payload['company'] = $data['company'];
            }
            if (!empty($data['phone'])) {
                $payload['phone'] = $data['phone'];
            }

            // Log the payload BEFORE sending (mask API key for security)
            $logPayload = $payload;
            $logPayload['access_key'] = substr($apiKey, 0, 8) . '...' . substr($apiKey, -4);
            Log::info('Web3Forms API Request Payload', [
                'url' => 'https://api.web3forms.com/submit',
                'method' => 'POST',
                'content_type' => 'application/x-www-form-urlencoded',
                'payload' => $logPayload,
                'payload_keys' => array_keys($payload),
                'payload_count' => count($payload),
            ]);

            // Web3Forms accepts form-urlencoded data
            // Using asForm() ensures proper Content-Type header
            // Enhanced headers for Cloudflare compatibility
            // Note: Cloudflare may still block server-side requests - contact Web3Forms support for IP whitelisting
            $startTime = microtime(true);
            $response = Http::asForm()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => config('app.url'),
                    'Referer' => config('app.url') . '/contact',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'cross-site',
                    'Cache-Control' => 'no-cache',
                ])
                ->timeout(15)
                ->post('https://api.web3forms.com/submit', $payload);
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $responseBody = $response->body();
            $responseData = $response->json();
            $statusCode = $response->status();

            // Log the full response for debugging
            Log::info('Web3Forms API Response', [
                'status_code' => $statusCode,
                'response_time_ms' => $duration,
                'response_body_preview' => substr($responseBody, 0, 500),
                'response_body_length' => strlen($responseBody),
                'response_data' => $responseData,
                'cf_ray' => $response->header('CF-RAY'),
                'cf_mitigated' => $response->header('cf-mitigated'),
                'is_successful' => $response->successful(),
                'is_client_error' => $response->clientError(),
                'is_server_error' => $response->serverError(),
            ]);
            
            // Check if Cloudflare challenge detected
            if ($statusCode === 403 && (str_contains($responseBody, 'Just a moment') || str_contains($responseBody, 'cf-challenge'))) {
                Log::warning('Web3Forms blocked by Cloudflare challenge', [
                    'cf_ray' => $response->header('CF-RAY'),
                    'recommendation' => 'Contact Web3Forms support for IP whitelisting. See WEB3FORMS_DEEP_INVESTIGATION.md for details.',
                ]);
            }

            // Check if request was successful
            if ($response->successful()) {
                // Web3Forms returns success: true in the response
                if (isset($responseData['success']) && $responseData['success'] === true) {
                    Log::info('Web3Forms submission successful', [
                        'message_id' => $responseData['message_id'] ?? null,
                        'response' => $responseData,
                    ]);
                    return 'success';
                } else {
                    // API returned 200 but success is false
                    Log::warning('Web3Forms submission returned success=false', [
                        'response' => $responseData,
                        'status_code' => $statusCode,
                    ]);
                    return 'failed';
                }
            } else {
                // HTTP error status
                Log::error('Web3Forms API HTTP error', [
                    'status' => $statusCode,
                    'response' => $responseBody,
                    'response_data' => $responseData,
                ]);
                return 'failed';
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Web3Forms API Connection Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 'failed';
        } catch (\Exception $e) {
            Log::error('Web3Forms API Exception', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'failed';
        }
    }

    /**
     * Send internal notification email
     */
    private function sendNotificationEmail(ContactSubmission $submission): string
    {
        // Use mail from address as the notification recipient
        $supportEmail = config('mail.from.address', 'support@legitbooks.com');
        
        Log::info('Preparing to send notification email', [
            'submission_id' => $submission->id,
            'recipient' => $supportEmail,
            'mail_mailer' => config('mail.default'),
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
        ]);

        try {
            $mailable = new ContactNotification($submission);
            
            Log::info('Mailable created, attempting to send', [
                'submission_id' => $submission->id,
                'mailable_class' => get_class($mailable),
            ]);
            
            Mail::to($supportEmail)->send($mailable);
            
            Log::info('Notification email sent successfully', [
                'submission_id' => $submission->id,
                'recipient' => $supportEmail,
            ]);
            
            return 'sent';
        } catch (\Swift_TransportException $e) {
            Log::error('Mail transport exception', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'submission_id' => $submission->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return 'failed';
        } catch (\Exception $e) {
            Log::error('Failed to send contact notification email', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'submission_id' => $submission->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return 'failed';
        }
    }
}

