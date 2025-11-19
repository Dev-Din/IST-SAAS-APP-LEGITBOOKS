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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
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

        // Submit to Web3Forms (server-side only)
        $web3formsStatus = $this->submitToWeb3Forms($validated);
        $submission->web3forms_status = $web3formsStatus;
        $submission->save();

        // Send internal notification email
        $mailStatus = $this->sendNotificationEmail($submission);
        $submission->mail_status = $mailStatus;
        $submission->save();

        // Return JSON if AJAX request, otherwise redirect
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message! We\'ll get back to you soon.',
            ]);
        }

        return redirect()->route('marketing.contact')
            ->with('success', 'Thank you for your message! We\'ll get back to you soon.');
    }

    /**
     * Submit form data to Web3Forms API
     * Reference: https://docs.web3forms.com/
     */
    private function submitToWeb3Forms(array $data): string
    {
        $apiKey = env('WEB3FORMS_API_KEY');
        
        if (!$apiKey) {
            Log::warning('Web3Forms API key not configured. Please set WEB3FORMS_API_KEY in .env file');
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

            // Web3Forms accepts form-urlencoded data
            // Using asForm() ensures proper Content-Type header
            $response = Http::asForm()
                ->timeout(15)
                ->post('https://api.web3forms.com/submit', $payload);

            $responseBody = $response->body();
            $responseData = $response->json();

            // Log the full response for debugging
            Log::info('Web3Forms API Response', [
                'status_code' => $response->status(),
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            // Check if request was successful
            if ($response->successful()) {
                // Web3Forms returns success: true in the response
                if (isset($responseData['success']) && $responseData['success'] === true) {
                    Log::info('Web3Forms submission successful', [
                        'message_id' => $responseData['message_id'] ?? null,
                    ]);
                    return 'success';
                } else {
                    // API returned 200 but success is false
                    Log::warning('Web3Forms submission returned success=false', [
                        'response' => $responseData,
                    ]);
                    return 'failed';
                }
            } else {
                // HTTP error status
                Log::error('Web3Forms API HTTP error', [
                    'status' => $response->status(),
                    'response' => $responseBody,
                ]);
                return 'failed';
            }
        } catch (\Exception $e) {
            Log::error('Web3Forms API exception', [
                'error' => $e->getMessage(),
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

        try {
            Mail::to($supportEmail)->send(new ContactNotification($submission));
            return 'sent';
        } catch (\Exception $e) {
            Log::error('Failed to send contact notification email', [
                'error' => $e->getMessage(),
                'submission_id' => $submission->id,
            ]);

            return 'failed';
        }
    }
}

