<?php

namespace Tests\Feature\Marketing;

use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set required environment variables for tests
        putenv('WEB3FORMS_API_KEY=2b37e16d-620e-4d6b-b370-4023bbe9fde1');
    }

    /**
     * Test that contact form can be submitted with valid data.
     */
    public function test_contact_form_submission_with_valid_data(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Test Company',
            'phone' => '+1234567890',
            'message' => 'This is a test message.',
        ]);

        $response->assertRedirect(route('marketing.contact'));
        $response->assertSessionHas('success');

        // Verify submission was saved
        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Test Company',
            'phone' => '+1234567890',
        ]);

        // Verify email was sent
        Mail::assertSent(\App\Mail\ContactNotification::class);
    }

    /**
     * Test that contact form validates required fields.
     */
    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->post('/contact', []);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
    }

    /**
     * Test that contact form validates email format.
     */
    public function test_contact_form_validates_email_format(): void
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'Test message',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test that contact form accepts optional fields.
     */
    public function test_contact_form_accepts_optional_fields(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ]);

        $response->assertRedirect(route('marketing.contact'));
        $response->assertSessionHas('success');

        // Verify submission was saved without optional fields
        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => null,
            'phone' => null,
        ]);
    }

    /**
     * Test that contact form validates message length.
     */
    public function test_contact_form_validates_message_length(): void
    {
        $longMessage = str_repeat('a', 5001); // Exceeds 5000 character limit

        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => $longMessage,
        ]);

        $response->assertSessionHasErrors(['message']);
    }

    /**
     * Test that submission is saved to database.
     */
    public function test_submission_is_saved_to_database(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => true], 200),
        ]);

        $this->post('/contact', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'company' => 'Acme Corp',
            'phone' => '+9876543210',
            'message' => 'Hello, I need help with your service.',
        ]);

        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'company' => 'Acme Corp',
            'phone' => '+9876543210',
            'message' => 'Hello, I need help with your service.',
        ]);
    }

    /**
     * Test Web3Forms POST is made (mocked).
     */
    public function test_web3forms_post_is_made(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => true], 200),
        ]);

        $this->post('/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.web3forms.com/submit' &&
                   $request->method() === 'POST' &&
                   $request['access_key'] === '2b37e16d-620e-4d6b-b370-4023bbe9fde1' &&
                   $request['name'] === 'Test User' &&
                   $request['email'] === 'test@example.com';
        });
    }

    /**
     * Test email sending (Mail fake).
     */
    public function test_email_is_sent(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => true], 200),
        ]);

        $this->post('/contact', [
            'name' => 'Email Test',
            'email' => 'email@example.com',
            'message' => 'Test email sending',
        ]);

        Mail::assertSent(\App\Mail\ContactNotification::class, function ($mail) {
            return $mail->submission->email === 'email@example.com';
        });
    }

    /**
     * Test redirect with success message.
     */
    public function test_redirect_with_success_message(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/contact', [
            'name' => 'Success Test',
            'email' => 'success@example.com',
            'message' => 'Testing success redirect',
        ]);

        $response->assertRedirect(route('marketing.contact'));
        $response->assertSessionHas('success', 'Thank you for your message! We\'ll get back to you soon.');
    }

    /**
     * Test JSON response for AJAX requests.
     */
    public function test_json_response_for_ajax_requests(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => true], 200),
        ]);

        $response = $this->postJson('/contact', [
            'name' => 'AJAX Test',
            'email' => 'ajax@example.com',
            'message' => 'Testing AJAX response',
        ]);

        $response->assertJson([
            'success' => true,
            'message' => 'Thank you for your message! We\'ll get back to you soon.',
        ]);
    }

    /**
     * Test Web3Forms failure handling.
     */
    public function test_web3forms_failure_handling(): void
    {
        Mail::fake();
        Http::fake([
            'api.web3forms.com/submit' => Http::response(['success' => false], 400),
        ]);

        $response = $this->post('/contact', [
            'name' => 'Failure Test',
            'email' => 'failure@example.com',
            'message' => 'Testing failure handling',
        ]);

        $response->assertRedirect(route('marketing.contact'));
        $response->assertSessionHas('success');

        // Submission should still be saved with failed status
        $this->assertDatabaseHas('contact_submissions', [
            'email' => 'failure@example.com',
            'web3forms_status' => 'failed',
        ]);
    }
}

